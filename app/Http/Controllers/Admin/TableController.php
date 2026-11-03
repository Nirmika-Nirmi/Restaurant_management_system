<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reservation\Reservation;
use App\Models\Reservation\RestaurantTable;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TableController extends Controller
{
    public function index(Request $request)
    {
        // next upcoming booking of each table
        $next = Reservation::query()
            ->selectRaw('MIN(reservation_at)')
            ->whereColumn('reservations.table_id', 'restaurant_tables.id')
            ->whereIn('status', ['pending', 'confirmed'])
            ->where('reservation_at', '>=', now());

        $tables = RestaurantTable::query()
            ->select('restaurant_tables.*')
            ->selectSub($next, 'next_booking')
            ->orderBy('table_number')
            ->get();

        $editId = $request->integer('edit');

        return view('admin.tables', [
            'tables' => $tables,
            'edit'   => $editId ? $tables->firstWhere('id', $editId) : null,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'table_number' => ['required', 'string', 'max:20', 'unique:restaurant_tables,table_number'],
            'capacity'     => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        RestaurantTable::create([
            'table_number' => $data['table_number'],
            'capacity'     => $data['capacity'],
            'status'       => 'available',
            'is_active'    => true,
        ]);

        return redirect()->route('admin.tables')->with('ok', 'Table added.');
    }

    public function update(Request $request, RestaurantTable $table)
    {
        $data = $request->validate([
            'table_number' => ['required', 'string', 'max:20',
                               Rule::unique('restaurant_tables', 'table_number')->ignore($table->id)],
            'capacity'     => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $table->update($data);

        return redirect()->route('admin.tables')->with('ok', 'Table updated.');
    }

    public function setStatus(Request $request, RestaurantTable $table)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(RestaurantTable::STATUS_LABELS))],
        ]);

        $table->update(['status' => $data['status']]);

        return back()->with('ok', 'Table ' . $table->table_number . ' is now ' . strtolower(RestaurantTable::STATUS_LABELS[$data['status']]) . '.');
    }

    public function toggle(RestaurantTable $table)
    {
        $table->update(['is_active' => ! $table->is_active]);

        return back()->with('ok', $table->is_active ? 'Table activated.' : 'Table deactivated.');
    }
}