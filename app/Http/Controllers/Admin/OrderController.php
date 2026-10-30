<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order\Order;
use App\Models\Order\OrderStatusHistory;
use App\Models\Order\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $status = (string) $request->query('status', '');
        $type   = (string) $request->query('type', '');
        $date   = (string) $request->query('date', '');
        $search = trim((string) $request->query('q', ''));

        if (! isset(Order::STATUS_LABELS[$status])) {
            $status = '';
        }
        if (! isset(Order::TYPE_LABELS[$type])) {
            $type = '';
        }
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $date = '';
        }

        // numbers shown on the status tabs
        $counts = Order::query()
            ->selectRaw('status, COUNT(*) AS c')
            ->groupBy('status')
            ->pluck('c', 'status');

        $orders = Order::with('customer')
            ->withCount('items')
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->when($type !== '', fn ($q) => $q->where('order_type', $type))
            ->when($date !== '', fn ($q) => $q->whereDate('placed_at', $date))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($w) use ($search) {
                    $w->where('order_number', 'like', "%{$search}%")
                      ->orWhereHas('customer', function ($c) use ($search) {
                          $c->where('full_name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                      });
                });
            })
            ->orderByDesc('placed_at')
            ->paginate(15)
            ->withQueryString();

        return view('admin.orders', [
            'orders' => $orders,
            'counts' => $counts,
            'status' => $status,
            'type'   => $type,
            'date'   => $date,
            'search' => $search,
        ]);
    }

    public function show(Order $order)
    {
        $order->load(['customer', 'items.addons', 'history.admin', 'payments']);

        $tableNumber = $order->table_id
            ? DB::table('restaurant_tables')->where('id', $order->table_id)->value('table_number')
            : null;

        $zoneName = $order->delivery_zone_id
            ? DB::table('delivery_zones')->where('id', $order->delivery_zone_id)->value('name')
            : null;

        return view('admin.order_view', [
            'order'       => $order,
            'tableNumber' => $tableNumber,
            'zoneName'    => $zoneName,
        ]);
    }

    public function updateStatus(Request $request, Order $order)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(Order::STATUS_LABELS))],
            'note'   => ['nullable', 'string', 'max:255'],
        ]);

        $done = DB::transaction(function () use ($order, $data) {
            // lock the row so two people cannot change it at the same time
            $locked = Order::lockForUpdate()->findOrFail($order->id);

            if (! in_array($data['status'], $locked->nextStatuses(), true)) {
                return false;
            }

            $old = $locked->status;
            $locked->update(['status' => $data['status']]);

            $this->history($locked, $data['status'], $data['note'] ?? null);
            $this->audit('status', $locked->id, ['status' => $old], ['status' => $data['status']]);

            return true;
        });

        if (! $done) {
            return back()->with('error', 'That status change is not allowed. Someone may have updated this order already.');
        }

        return back()->with('ok', 'Order #' . $order->order_number . ' marked as ' . Order::STATUS_LABELS[$data['status']] . '.');
    }

    public function cancel(Request $request, Order $order)
    {
        $data = $request->validate([
            'cancel_reason' => ['required', 'string', 'max:255'],
        ]);

        $done = DB::transaction(function () use ($order, $data) {
            $locked = Order::lockForUpdate()->findOrFail($order->id);

            if (! $locked->canCancel()) {
                return false;
            }

            $old = $locked->status;
            $locked->update([
                'status'        => 'cancelled',
                'cancel_reason' => $data['cancel_reason'],
            ]);

            $this->history($locked, 'cancelled', $data['cancel_reason']);
            $this->audit('cancel', $locked->id, ['status' => $old], ['status' => 'cancelled']);

            return true;
        });

        if (! $done) {
            return back()->with('error', 'This order can no longer be cancelled.');
        }

        $msg = 'Order #' . $order->order_number . ' cancelled.';
        if ($order->payment_status === 'paid') {
            $msg .= ' It was already paid, remember to refund the customer.';
        }

        return back()->with('ok', $msg);
    }

    public function markPaid(Request $request, Order $order)
    {
        $data = $request->validate([
            'method'          => ['required', Rule::in(['cash', 'card', 'online', 'wallet'])],
            'transaction_ref' => ['nullable', 'string', 'max:100'],
        ]);

        $done = DB::transaction(function () use ($order, $data) {
            $locked = Order::lockForUpdate()->findOrFail($order->id);

            if ($locked->payment_status === 'paid' || $locked->status === 'cancelled') {
                return false;
            }

            Payment::create([
                'order_id'        => $locked->id,
                'method'          => $data['method'],
                'transaction_ref' => $data['transaction_ref'] ?? null,
                'amount'          => $locked->total_amount,
                'status'          => 'success',
                'paid_at'         => now(),
            ]);

            $locked->update(['payment_status' => 'paid']);

            $this->audit('payment', $locked->id, ['payment_status' => 'unpaid'], [
                'payment_status' => 'paid',
                'method'         => $data['method'],
            ]);

            return true;
        });

        if (! $done) {
            return back()->with('error', 'Payment cannot be recorded for this order.');
        }

        return back()->with('ok', 'Payment recorded for order #' . $order->order_number . '.');
    }

    /* ---------------- helpers ---------------- */

    private function history(Order $order, string $status, ?string $note): void
    {
        OrderStatusHistory::create([
            'order_id'   => $order->id,
            'status'     => $status,
            'changed_by' => Auth::guard('admin')->id(),
            'note'       => $note,
        ]);
    }

    private function audit(string $action, int $entityId, ?array $old, ?array $new): void
    {
        DB::table('audit_logs')->insert([
            'admin_id'   => Auth::guard('admin')->id(),
            'action'     => $action,
            'entity'     => 'order',
            'entity_id'  => $entityId,
            'old_data'   => $old ? json_encode($old) : null,
            'new_data'   => $new ? json_encode($new) : null,
            'ip_address' => request()->ip(),
            'created_at' => now(),
        ]);
    }
}