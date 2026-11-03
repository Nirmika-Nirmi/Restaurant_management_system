<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

class DashboardController extends Controller
{
    
    public function home()
    {
        /** @var \App\Models\Admin\Config\AdminUser $admin */
        $admin = Auth::guard('admin')->user();

        $pages = [
            'dashboard.view'    => 'admin.dashboard',
            'orders.view'       => 'admin.orders',
            'reservations.view' => 'admin.reservations',
            'menu.view'         => 'admin.menu',
            'customers.view'    => 'admin.customers',
            'users.view'        => 'admin.users',
        ];

        foreach ($pages as $permission => $route) {
            if (Route::has($route) && $admin->can($permission)) {
                return redirect()->route($route);
            }
        }

        if ($admin->hasRole('super_admin')) {
            return redirect()->route('admin.roles');
        }

        abort(403, 'No pages are assigned to your role yet. Contact the super admin.');
    }

    public function index()
    {
      
        $today = DB::table('orders')
            ->whereDate('placed_at', today())
            ->where('status', '<>', 'cancelled')
            ->selectRaw('COUNT(*) AS orders, COALESCE(SUM(total_amount), 0) AS revenue')
            ->first();

        $pendingOrders = DB::table('orders')->where('status', 'pending')->count();

        $upcomingRes = DB::table('reservations')
            ->where('status', 'pending')
            ->where('reservation_at', '>=', now())
            ->count();

        $totalCustomers = DB::table('customers')->count();

        $menuCount = DB::table('menu_items')
            ->where('is_available', 1)
            ->where('is_active', 1)
            ->count();

        $newMessages = DB::table('contact_messages')
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        
        $rows = DB::table('orders')
            ->where('placed_at', '>=', today()->subDays(6))
            ->where('status', '<>', 'cancelled')
            ->selectRaw('DATE(placed_at) AS d, SUM(total_amount) AS revenue')
            ->groupByRaw('DATE(placed_at)')
            ->pluck('revenue', 'd');

        $week = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = today()->subDays($i);
            $week[] = [
                'day'    => $date->format('D'),
                'amount' => (float) ($rows[$date->format('Y-m-d')] ?? 0),
                'today'  => $i === 0,
            ];
        }
        $maxAmount = max(array_column($week, 'amount')) ?: 1;

        
        $topItems = DB::table('order_items as oi')
            ->join('orders as o', 'o.id', '=', 'oi.order_id')
            ->where('o.status', '<>', 'cancelled')
            ->where('o.placed_at', '>=', now()->subDays(30))
            ->selectRaw('oi.item_name AS name, SUM(oi.quantity) AS qty')
            ->groupBy('oi.item_name')
            ->orderByDesc('qty')
            ->limit(5)
            ->get();

       
        $recent = DB::table('orders as o')
            ->leftJoin('customers as c', 'c.id', '=', 'o.customer_id')
            ->selectRaw("o.id, o.order_number, COALESCE(c.full_name, 'Guest') AS customer,
                         o.order_type, o.status, o.total_amount, o.placed_at")
            ->orderByDesc('o.placed_at')
            ->limit(10)
            ->get();

        return view('admin.dashboard', [
            'admin'          => Auth::guard('admin')->user(),
            'today'          => $today,
            'pendingOrders'  => $pendingOrders,
            'upcomingRes'    => $upcomingRes,
            'totalCustomers' => $totalCustomers,
            'menuCount'      => $menuCount,
            'newMessages'    => $newMessages,
            'week'           => $week,
            'maxAmount'      => $maxAmount,
            'topItems'       => $topItems,
            'recent'         => $recent,
        ]);
    }
}