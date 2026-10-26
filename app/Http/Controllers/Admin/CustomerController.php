<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
        public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');

        $ordersCount = DB::table('orders')
            ->selectRaw('COUNT(*)')
            ->whereColumn('orders.customer_id', 'customers.id')
            ->where('orders.status', '<>', 'cancelled');

        $totalSpent = DB::table('orders')
            ->selectRaw('COALESCE(SUM(orders.total_amount), 0)')
            ->whereColumn('orders.customer_id', 'customers.id')
            ->where('orders.status', '<>', 'cancelled');

        $customers = Customer::query()
            ->select('customers.*')
            ->selectSub($ordersCount, 'orders_count')
            ->selectSub($totalSpent, 'total_spent')
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($w) use ($search) {
                    $w->where('customers.full_name', 'like', "%{$search}%")
                      ->orWhere('customers.email', 'like', "%{$search}%")
                      ->orWhere('customers.phone', 'like', "%{$search}%");
                });
            })
            ->when($status === 'active', fn ($q) => $q->where('customers.is_active', 1))
            ->when($status === 'inactive', fn ($q) => $q->where('customers.is_active', 0))
            ->orderByDesc('customers.created_at')
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'total'     => Customer::count(),
            'active'    => Customer::where('is_active', 1)->count(),
            'thisMonth' => Customer::where('created_at', '>=', now()->startOfMonth())->count(),
        ];

        return view('admin.customers', [
            'customers' => $customers,
            'stats'     => $stats,
            'search'    => $search,
            'status'    => $status,
        ]);
    }

    public function show(Customer $customer)
    {
        $addresses = $customer->addresses()
            ->orderByDesc('is_default')
            ->get();

        $orders = DB::table('orders')
            ->where('customer_id', $customer->id)
            ->orderByDesc('placed_at')
            ->limit(10)
            ->get();

        $totals = DB::table('orders')
            ->where('customer_id', $customer->id)
            ->where('status', '<>', 'cancelled')
            ->selectRaw('COUNT(*) AS orders_count, COALESCE(SUM(total_amount), 0) AS total_spent')
            ->first();

        return view('admin.customer_view', [
            'customer'  => $customer,
            'addresses' => $addresses,
            'orders'    => $orders,
            'totals'    => $totals,
        ]);
    }

    public function toggle(Customer $customer)
    {
        $customer->update(['is_active' => ! $customer->is_active]);

        DB::table('audit_logs')->insert([
            'admin_id'   => Auth::guard('admin')->id(),
            'action'     => 'toggle',
            'entity'     => 'customer',
            'entity_id'  => $customer->id,
            'old_data'   => null,
            'new_data'   => json_encode(['is_active' => $customer->is_active]),
            'ip_address' => request()->ip(),
            'created_at' => now(),
        ]);

        return back()->with('ok', $customer->is_active ? 'Customer activated.' : 'Customer deactivated.');
    }
}