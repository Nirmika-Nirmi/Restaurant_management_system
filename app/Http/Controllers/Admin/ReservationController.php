<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer\Customer;
use App\Models\Reservation\Reservation;
use App\Models\Reservation\RestaurantTable;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ReservationController extends Controller
{
    // a booking blocks its table for this many hours
    private const SLOT_HOURS = 2;

    public function index(Request $request)
    {
        return $this->page($request);
    }

    public function edit(Request $request, Reservation $reservation)
    {
        return $this->page($request, $reservation);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request, true);
        $at   = Carbon::createFromFormat('Y-m-d\TH:i', $data['reservation_at']);

        if ($problem = $this->tableProblem($data['table_id'] ?? null, (int) $data['party_size'], $at, null)) {
            return back()->withInput()->with('error', $problem);
        }

        $reservation = Reservation::create([
            'customer_id'    => Customer::where('phone', $data['guest_phone'])->value('id'),
            'guest_name'     => $data['guest_name'],
            'guest_phone'    => $data['guest_phone'],
            'table_id'       => $data['table_id'] ?? null,
            'party_size'     => $data['party_size'],
            'reservation_at' => $at,
            'notes'          => $data['notes'] ?? null,
            'status'         => 'confirmed', // booked by staff (phone / walk-in)
        ]);

        $this->audit('create', $reservation->id, null, $reservation->toArray());

        return redirect()->route('admin.reservations')->with('ok', 'Reservation added.');
    }

    public function update(Request $request, Reservation $reservation)
    {
        if (! $reservation->isEditable()) {
            return redirect()->route('admin.reservations')
                ->with('error', 'This reservation can no longer be edited.');
        }

        $data = $this->validated($request, false);
        $at   = Carbon::createFromFormat('Y-m-d\TH:i', $data['reservation_at']);

        if ($problem = $this->tableProblem($data['table_id'] ?? null, (int) $data['party_size'], $at, $reservation->id)) {
            return back()->withInput()->with('error', $problem);
        }

        $old = $reservation->toArray();

        $reservation->update([
            'customer_id'    => Customer::where('phone', $data['guest_phone'])->value('id'),
            'guest_name'     => $data['guest_name'],
            'guest_phone'    => $data['guest_phone'],
            'table_id'       => $data['table_id'] ?? null,
            'party_size'     => $data['party_size'],
            'reservation_at' => $at,
            'notes'          => $data['notes'] ?? null,
        ]);

        $this->audit('update', $reservation->id, $old, $reservation->fresh()->toArray());

        return redirect()->route('admin.reservations')->with('ok', 'Reservation updated.');
    }

    public function updateStatus(Request $request, Reservation $reservation)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(Reservation::STATUS_LABELS))],
        ]);

        $error = DB::transaction(function () use ($reservation, $data) {
            $locked = Reservation::lockForUpdate()->findOrFail($reservation->id);

            if (! in_array($data['status'], $locked->nextStatuses(), true)) {
                return 'That change is not allowed. Someone may have updated this reservation already.';
            }

            if ($data['status'] === 'seated' && ! $locked->table_id) {
                return 'Assign a table (Edit) before seating the guests.';
            }

            $old = $locked->status;
            $locked->update(['status' => $data['status']]);

            if ($locked->table_id) {
                if ($data['status'] === 'seated') {
                    RestaurantTable::whereKey($locked->table_id)->update(['status' => 'occupied']);
                } elseif ($data['status'] === 'completed') {
                    RestaurantTable::whereKey($locked->table_id)
                        ->where('status', 'occupied')
                        ->update(['status' => 'cleaning']);
                }
            }

            $this->audit('status', $locked->id, ['status' => $old], ['status' => $data['status']]);

            return null;
        });

        if ($error) {
            return back()->with('error', $error);
        }

        return back()->with('ok', $reservation->guest_name . ': ' . Reservation::STATUS_LABELS[$data['status']] . '.');
    }

    /* ---------------- helpers ---------------- */

    private function page(Request $request, ?Reservation $edit = null)
    {
        $status = (string) $request->query('status', '');
        $when   = (string) $request->query('when', 'upcoming');
        $search = trim((string) $request->query('q', ''));

        if (! isset(Reservation::STATUS_LABELS[$status])) {
            $status = '';
        }
        if (! in_array($when, ['upcoming', 'today', 'past', 'all'], true)) {
            $when = 'upcoming';
        }

        $base = Reservation::query()
            ->when($when === 'upcoming', fn ($q) => $q->where('reservation_at', '>=', today()))
            ->when($when === 'today', fn ($q) => $q->whereDate('reservation_at', today()))
            ->when($when === 'past', fn ($q) => $q->where('reservation_at', '<', today()))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($w) use ($search) {
                    $w->where('guest_name', 'like', "%{$search}%")
                      ->orWhere('guest_phone', 'like', "%{$search}%");
                });
            });

        $counts = (clone $base)
            ->selectRaw('status, COUNT(*) AS c')
            ->groupBy('status')
            ->pluck('c', 'status');

        $reservations = (clone $base)
            ->with('table')
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->orderBy('reservation_at', $when === 'past' ? 'desc' : 'asc')
            ->paginate(15)
            ->withQueryString();

        return view('admin.reservations', [
            'reservations' => $reservations,
            'counts'       => $counts,
            'status'       => $status,
            'when'         => $when,
            'search'       => $search,
            'tables'       => RestaurantTable::where('is_active', 1)->orderBy('table_number')->get(),
            'edit'         => $edit,
        ]);
    }

    private function validated(Request $request, bool $mustBeFuture): array
    {
        $dateRules = ['required', 'date_format:Y-m-d\TH:i'];
        if ($mustBeFuture) {
            $dateRules[] = 'after:now';
        }

        return $request->validate([
            'guest_name'     => ['required', 'string', 'max:120'],
            'guest_phone'    => ['required', 'string', 'max:30'],
            'party_size'     => ['required', 'integer', 'min:1', 'max:100'],
            'reservation_at' => $dateRules,
            'table_id'       => ['nullable', 'exists:restaurant_tables,id'],
            'notes'          => ['nullable', 'string', 'max:255'],
        ]);
    }

    // returns an error message, or null when the table is fine
    private function tableProblem(?int $tableId, int $partySize, Carbon $at, ?int $ignoreId): ?string
    {
        if (! $tableId) {
            return null;
        }

        $table = RestaurantTable::find($tableId);

        if (! $table || ! $table->is_active) {
            return 'The selected table is not available.';
        }

        if ($partySize > $table->capacity) {
            return "Table {$table->table_number} seats only {$table->capacity} people.";
        }

        $clash = Reservation::where('table_id', $tableId)
            ->whereIn('status', ['pending', 'confirmed', 'seated'])
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->where('reservation_at', '>', $at->copy()->subHours(self::SLOT_HOURS))
            ->where('reservation_at', '<', $at->copy()->addHours(self::SLOT_HOURS))
            ->exists();

        if ($clash) {
            return "Table {$table->table_number} already has a booking around that time.";
        }

        return null;
    }

    private function audit(string $action, int $entityId, ?array $old, ?array $new): void
    {
        DB::table('audit_logs')->insert([
            'admin_id'   => Auth::guard('admin')->id(),
            'action'     => $action,
            'entity'     => 'reservation',
            'entity_id'  => $entityId,
            'old_data'   => $old ? json_encode($old) : null,
            'new_data'   => $new ? json_encode($new) : null,
            'ip_address' => request()->ip(),
            'created_at' => now(),
        ]);
    }
}