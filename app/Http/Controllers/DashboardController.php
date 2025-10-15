<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Employee;
use App\Models\OvertimeRequest;
use App\Models\OvertimeDetail;
use App\Models\OvertimePlanning;
use App\Models\OvertimeApproval;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $currentUser = Auth::user();
        $currentEmployee = Employee::where('email', $currentUser->email)->first();
        
        // ===== STATISTICS CARDS =====
        
        // 1. Total SPK Lembur Bulan Ini
        $totalSPKLThisMonth = OvertimeRequest::whereYear('created_at', date('Y'))
            ->whereMonth('created_at', date('m'))
            ->count();

        // 2. Planning Lembur Aktif (status approved & tanggal >= hari ini & masih ada kuota)
        $activePlanning = OvertimePlanning::where('status', 'approved')
            ->where('planned_date', '>=', now()->format('Y-m-d'))
            ->where('remaining_employees', '>', 0)
            ->count();

        // 3. Menunggu Approval Saya (kalau user adalah approver)
        $pendingMyApproval = 0;
        if ($currentEmployee) {
            $pendingMyApproval = OvertimeApproval::where('approver_employee_id', $currentEmployee->id)
                ->where('status', 'pending')
                ->whereHas('overtimeRequest', function($q) {
                    $q->whereIn('status', ['pending', 'approved_sect', 'approved_subdept', 'approved_dept', 'approved_subdiv', 'approved_div']);
                })
                ->count();
        }

        // 4. Total Jam Lembur Bulan Ini (completed only)
        $totalHoursThisMonth = OvertimeDetail::whereHas('overtimeRequest', function($q) {
                $q->where('status', 'completed')
                  ->whereYear('date', date('Y'))
                  ->whereMonth('date', date('m'));
            })
            ->where('is_rejected', false)
            ->get()
            ->sum(function($detail) {
                $start = Carbon::parse($detail->start_time);
                $end = Carbon::parse($detail->end_time);
                return $end->diffInMinutes($start);
            });
        
        $totalHoursFormatted = $this->formatMinutesToHours($totalHoursThisMonth);

        // ===== BREAKDOWN STATUS SPKL =====
        $statusBreakdown = [
            'pending' => OvertimeRequest::whereIn('status', ['pending', 'approved_sect', 'approved_subdept', 'approved_dept', 'approved_subdiv', 'approved_div'])->count(),
            'approved' => OvertimeRequest::where('status', 'approved')->count(),
            'completed' => OvertimeRequest::where('status', 'completed')->count(),
            'rejected' => OvertimeRequest::where('status', 'rejected')->count(),
        ];

        // ===== TOP 5 DEPARTMENT (BULAN INI) =====
        $topDepartments = OvertimeDetail::select('overtime_requests.department_id', 'departments.name as department_name')
            ->join('overtime_requests', 'overtime_details.overtime_request_id', '=', 'overtime_requests.id')
            ->join('departments', 'overtime_requests.department_id', '=', 'departments.id')
            ->whereHas('overtimeRequest', function($q) {
                $q->where('status', 'completed')
                  ->whereYear('date', date('Y'))
                  ->whereMonth('date', date('m'));
            })
            ->where('overtime_details.is_rejected', false)
            ->groupBy('overtime_requests.department_id', 'departments.name')
            ->selectRaw('SUM(TIMESTAMPDIFF(MINUTE, overtime_details.start_time, overtime_details.end_time)) as total_minutes')
            ->orderByDesc('total_minutes')
            ->limit(5)
            ->get()
            ->map(function($item) {
                return [
                    'department' => $item->department_name,
                    'total_hours' => round($item->total_minutes / 60, 2),
                    'formatted_time' => $this->formatMinutesToHours($item->total_minutes)
                ];
            });

        // ===== PLANNING YANG PERLU PERHATIAN =====
        
        // Planning H-7 (perlu reminder)
        $planningsNeedReminder = OvertimePlanning::where('status', 'approved')
            ->where('planned_date', '=', now()->addDays(7)->format('Y-m-d'))
            ->where('remaining_employees', '>', 0)
            ->count();

        // Planning Expired (H+1 masih ada kuota)
        $planningsExpired = OvertimePlanning::where('status', 'approved')
            ->where('planned_date', '<', now()->format('Y-m-d'))
            ->where('remaining_employees', '>', 0)
            ->count();

        // Planning Pending Approval
        $planningsPendingApproval = OvertimePlanning::where('status', 'pending')->count();

        return view('dashboard', compact(
            'totalSPKLThisMonth',
            'activePlanning',
            'pendingMyApproval',
            'totalHoursFormatted',
            'statusBreakdown',
            'topDepartments',
            'planningsNeedReminder',
            'planningsExpired',
            'planningsPendingApproval',
            'currentEmployee'
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
        
        // Query untuk menghitung SPKL per hari (berdasarkan tanggal lembur, bukan created_at)
        $dailyData = OvertimeRequest::select(
                DB::raw('DAY(date) as day'),
                DB::raw('COUNT(*) as count')
            )
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
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
        
        // Query untuk menghitung SPKL per bulan (berdasarkan tanggal lembur)
        $monthlyData = OvertimeRequest::select(
                DB::raw('MONTH(date) as month'),
                DB::raw('COUNT(*) as count')
            )
            ->whereYear('date', $year)
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

    /**
     * Get daily hours chart data untuk total jam lembur per hari dalam sebulan
     */
    public function getDailyHoursChartData(Request $request)
    {
        $month = $request->input('month', date('n'));
        $year = $request->input('year', date('Y'));
        
        // Dapatkan jumlah hari dalam bulan tersebut
        $daysInMonth = Carbon::create($year, $month, 1)->daysInMonth;
        
        // Query untuk menghitung total jam per hari
        $dailyHours = OvertimeDetail::select(
                DB::raw('DAY(overtime_requests.date) as day'),
                DB::raw('SUM(TIMESTAMPDIFF(MINUTE, overtime_details.start_time, overtime_details.end_time)) as total_minutes')
            )
            ->join('overtime_requests', 'overtime_details.overtime_request_id', '=', 'overtime_requests.id')
            ->where('overtime_details.is_rejected', false)
            ->whereYear('overtime_requests.date', $year)
            ->whereMonth('overtime_requests.date', $month)
            ->whereIn('overtime_requests.status', ['completed']) // Hanya yang completed
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('total_minutes', 'day');
        
        // Buat array labels dan values
        $labels = [];
        $values = [];
        
        for ($i = 1; $i <= $daysInMonth; $i++) {
            $labels[] = $i;
            $minutes = $dailyHours->get($i, 0);
            $values[] = round($minutes / 60, 2); // Convert ke jam (decimal)
        }
        
        return response()->json([
            'labels' => $labels,
            'values' => $values
        ]);
    }

    /**
     * Get monthly hours chart data untuk total jam lembur per bulan dalam setahun
     */
    public function getMonthlyHoursChartData(Request $request)
    {
        $year = $request->input('year', date('Y'));
        
        // Query untuk menghitung total jam per bulan
        $monthlyHours = OvertimeDetail::select(
                DB::raw('MONTH(overtime_requests.date) as month'),
                DB::raw('SUM(TIMESTAMPDIFF(MINUTE, overtime_details.start_time, overtime_details.end_time)) as total_minutes')
            )
            ->join('overtime_requests', 'overtime_details.overtime_request_id', '=', 'overtime_requests.id')
            ->where('overtime_details.is_rejected', false)
            ->whereYear('overtime_requests.date', $year)
            ->whereIn('overtime_requests.status', ['completed']) // Hanya yang completed
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total_minutes', 'month');
        
        // Nama bulan dalam bahasa Indonesia
        $monthNames = [
            'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
            'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'
        ];
        
        $labels = [];
        $values = [];
        
        for ($i = 1; $i <= 12; $i++) {
            $labels[] = $monthNames[$i - 1];
            $minutes = $monthlyHours->get($i, 0);
            $values[] = round($minutes / 60, 2); // Convert ke jam (decimal)
        }
        
        return response()->json([
            'labels' => $labels,
            'values' => $values
        ]);
    }

    /**
     * Helper: Format minutes to hours
     */
    private function formatMinutesToHours($minutes)
    {
        if ($minutes == 0) {
            return '0 jam 0 menit';
        }
        
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        return sprintf('%d jam %d menit', $hours, $mins);
    }
}