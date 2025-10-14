<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\OvertimeRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        // Total Users
        $totalUsers = User::count();

        // Total Requests
        $totalRequests = OvertimeRequest::count();

        // Pending Requests (status pending atau dalam proses approval)
        $pendingRequests = OvertimeRequest::whereIn('status', [
            'pending',
            'approved_section_head',
            'approved_department_head',
            'approved_division_head'
        ])->count();

        // Completed Requests
        $completedRequests = OvertimeRequest::where('status', 'completed')->count();

        // Recent Requests with pagination
        $requests = OvertimeRequest::with(['requesterEmployee', 'department'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('dashboard', compact(
            'totalUsers',
            'totalRequests',
            'pendingRequests',
            'completedRequests',
            'requests'
        ));
    }

    /**
     * Get daily chart data untuk SPKL per hari dalam sebulan
     */
    public function getDailyChartData(Request $request)
    {
        $month = $request->input('month', date('n'));
        $year = $request->input('year', date('Y'));
        
        // Dapatkan jumlah hari dalam bulan tersebut
        $daysInMonth = Carbon::create($year, $month, 1)->daysInMonth;
        
        // Query untuk menghitung SPKL per hari
        $dailyData = OvertimeRequest::select(
                DB::raw('DAY(created_at) as day'),
                DB::raw('COUNT(*) as count')
            )
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('count', 'day');
        
        // Buat array labels (1-31 atau sesuai jumlah hari)
        $labels = [];
        $values = [];
        
        for ($i = 1; $i <= $daysInMonth; $i++) {
            $labels[] = $i;
            $values[] = $dailyData->get($i, 0); // Default 0 jika tidak ada data
        }
        
        return response()->json([
            'labels' => $labels,
            'values' => $values
        ]);
    }

    /**
     * Get monthly chart data untuk SPKL per bulan dalam setahun
     */
    public function getMonthlyChartData(Request $request)
    {
        $year = $request->input('year', date('Y'));
        
        // Query untuk menghitung SPKL per bulan
        $monthlyData = OvertimeRequest::select(
                DB::raw('MONTH(created_at) as month'),
                DB::raw('COUNT(*) as count')
            )
            ->whereYear('created_at', $year)
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('count', 'month');
        
        // Nama bulan dalam bahasa Indonesia
        $monthNames = [
            'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
            'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'
        ];
        
        $labels = [];
        $values = [];
        
        for ($i = 1; $i <= 12; $i++) {
            $labels[] = $monthNames[$i - 1];
            $values[] = $monthlyData->get($i, 0); // Default 0 jika tidak ada data
        }
        
        return response()->json([
            'labels' => $labels,
            'values' => $values
        ]);
    }
}