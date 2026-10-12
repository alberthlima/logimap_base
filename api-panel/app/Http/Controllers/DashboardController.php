<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function stats(Request $request)
    {
        $totalClients = Client::count();
        $totalOrders = Order::count();

        // Conteo por estado
        $ordersByStatus = Order::select('status', DB::raw('count(*) as count'), DB::raw('sum(total_amount) as total'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $statusSummary = [
            'REGISTRADO' => $ordersByStatus['REGISTRADO'] ?? 0,
            'EN_CAMINO' => $ordersByStatus['EN_CAMINO'] ?? 0,
            'ENTREGADO' => $ordersByStatus['ENTREGADO'] ?? 0,
            'PAGADO' => $ordersByStatus['PAGADO'] ?? 0,
        ];

        // Monto total vendido
        $totalRevenue = Order::whereIn('status', ['ENTREGADO', 'PAGADO'])->sum('total_amount');

        // Distribución por distrito para gráficos
        $districtStats = Order::select('district', 'city', DB::raw('count(*) as order_count'), DB::raw('sum(total_amount) as total_revenue'))
            ->groupBy('district', 'city')
            ->orderBy('order_count', 'desc')
            ->get();

        // 5 pedidos más recientes
        $recentOrders = Order::with('client')
            ->orderBy('id', 'desc')
            ->take(6)
            ->get();

        return response()->json([
            'total_clients' => $totalClients,
            'total_orders' => $totalOrders,
            'total_revenue' => round($totalRevenue, 2),
            'status_summary' => $statusSummary,
            'district_stats' => $districtStats,
            'recent_orders' => $recentOrders,
        ]);
    }
}
