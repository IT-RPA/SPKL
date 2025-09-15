<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\OvertimeDetail;
use App\Models\OvertimeRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        // Get filter parameters
        $month = $request->get('month');
        $year = $request->get('year', date('Y'));
        $department_id = $request->get('department_id');
        
        // Build query for overtime leaderboard
        $query = Employee::with(['department', 'jobLevel'])
            ->whereHas('overtimeDetails', function ($q) use ($month, $year) {
                $q->whereHas('overtimeRequest', function ($subQ) use ($month, $year) {
                    $subQ->where('status', 'approved');
                    if ($month) {
                        $subQ->whereMonth('date', $month);
                    }
                    $subQ->whereYear('date', $year);
                });
            });

        if ($department_id) {
            $query->where('department_id', $department_id);
        }

        // Get employees with total overtime hours
        $employees = $query->get()->map(function ($employee) use ($month, $year) {
            $overtimeDetails = $employee->overtimeDetails()
                ->whereHas('overtimeRequest', function ($q) use ($month, $year) {
                    $q->where('status', 'approved');
                    if ($month) {
                        $q->whereMonth('date', $month);
                    }
                    $q->whereYear('date', $year);
                })
                ->get();

            $totalMinutes = 0;
            $totalRequests = 0;

            foreach ($overtimeDetails as $detail) {
                $startTime = Carbon::parse($detail->start_time);
                $endTime = Carbon::parse($detail->end_time);
                $totalMinutes += $endTime->diffInMinutes($startTime);
                $totalRequests++;
            }

            $totalHours = $totalMinutes / 60;

            return (object)[
                'id' => $employee->id,
                'employee_id' => $employee->employee_id,
                'name' => $employee->name,
                'department' => $employee->department->name,
                'job_level' => $employee->jobLevel->name,
                'total_hours' => round($totalHours, 2),
                'total_minutes' => $totalMinutes,
                'total_requests' => $totalRequests,
                'formatted_time' => $this->formatMinutesToHours($totalMinutes)
            ];
        })->sortByDesc('total_hours')->values();

        // Get departments for filter
        $departments = \App\Models\Department::orderBy('name')->get();

        if ($request->ajax()) {
            return response()->json([
                'data' => $employees
            ]);
        }

        return view('reports.overtime-leaderboard', compact('employees', 'departments', 'month', 'year', 'department_id'));
    }

    public function getEmployeeDetails(Request $request, $employeeId)
    {
        $month = $request->get('month');
        $year = $request->get('year', date('Y'));

        $employee = Employee::with(['department', 'jobLevel'])->findOrFail($employeeId);

        $details = OvertimeDetail::with(['overtimeRequest' => function($q) {
                $q->select('id', 'request_number', 'date');
            }])
            ->where('employee_id', $employeeId)
            ->whereHas('overtimeRequest', function ($q) use ($month, $year) {
                $q->where('status', 'approved');
                if ($month) {
                    $q->whereMonth('date', $month);
                }
                $q->whereYear('date', $year);
            })
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($detail) {
                $startTime = Carbon::parse($detail->start_time);
                $endTime = Carbon::parse($detail->end_time);
                $duration = $endTime->diffInMinutes($startTime);

                return [
                    'spk_number' => $detail->overtimeRequest->request_number,
                    'date' => Carbon::parse($detail->overtimeRequest->date)->format('d/m/Y'),
                    'start_time' => $detail->start_time,
                    'end_time' => $detail->end_time,
                    'duration_minutes' => $duration,
                    'formatted_duration' => $this->formatMinutesToHours($duration),
                    'work_priority' => $detail->work_priority,
                    'work_process' => $detail->work_process
                ];
            });

        return response()->json([
            'employee' => $employee,
            'details' => $details
        ]);
    }

    public function exportExcel(Request $request)
    {
        $month = $request->get('month');
        $year = $request->get('year', date('Y'));
        $department_id = $request->get('department_id');

        // Get the same data as index method
        $query = Employee::with(['department', 'jobLevel'])
            ->whereHas('overtimeDetails', function ($q) use ($month, $year) {
                $q->whereHas('overtimeRequest', function ($subQ) use ($month, $year) {
                    $subQ->where('status', 'approved');
                    if ($month) {
                        $subQ->whereMonth('date', $month);
                    }
                    $subQ->whereYear('date', $year);
                });
            });

        if ($department_id) {
            $query->where('department_id', $department_id);
        }

        $employees = $query->get()->map(function ($employee) use ($month, $year) {
            $overtimeDetails = $employee->overtimeDetails()
                ->whereHas('overtimeRequest', function ($q) use ($month, $year) {
                    $q->where('status', 'approved');
                    if ($month) {
                        $q->whereMonth('date', $month);
                    }
                    $q->whereYear('date', $year);
                })
                ->get();

            $totalMinutes = 0;
            $totalRequests = 0;

            foreach ($overtimeDetails as $detail) {
                $startTime = Carbon::parse($detail->start_time);
                $endTime = Carbon::parse($detail->end_time);
                $totalMinutes += $endTime->diffInMinutes($startTime);
                $totalRequests++;
            }

            return (object)[
                'employee_id' => $employee->employee_id,
                'name' => $employee->name,
                'department' => $employee->department->name,
                'job_level' => $employee->jobLevel->name,
                'total_hours' => round($totalMinutes / 60, 2),
                'total_requests' => $totalRequests,
                'formatted_time' => $this->formatMinutesToHours($totalMinutes)
            ];
        })->sortByDesc('total_hours')->values();

        // Create Excel file
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set title
        $periodText = $month ? Carbon::create($year, $month)->format('F Y') : $year;
        $sheet->setTitle('Overtime Leaderboard');
        $sheet->setCellValue('A1', 'LAPORAN LEADERBOARD LEMBUR');
        $sheet->setCellValue('A2', 'Periode: ' . $periodText);

        // Set headers
        $headers = ['No', 'ID Karyawan', 'Nama Karyawan', 'Department', 'Level Jabatan', 'Total Jam', 'Total Pengajuan'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '4', $header);
            $col++;
        }

        // Fill data
        $row = 5;
        $no = 1;
        foreach ($employees as $employee) {
            $sheet->setCellValue('A' . $row, $no++);
            $sheet->setCellValue('B' . $row, $employee->employee_id);
            $sheet->setCellValue('C' . $row, $employee->name);
            $sheet->setCellValue('D' . $row, $employee->department);
            $sheet->setCellValue('E' . $row, $employee->job_level);
            $sheet->setCellValue('F' . $row, $employee->formatted_time);
            $sheet->setCellValue('G' . $row, $employee->total_requests);
            $row++;
        }

        // Style the Excel
        $this->styleExcel($sheet, $row - 1);

        // Save and download
        $writer = new Xlsx($spreadsheet);
        $filename = 'overtime_leaderboard_' . $periodText . '_' . date('YmdHis') . '.xlsx';
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        $writer->save('php://output');
        exit;
    }

    private function formatMinutesToHours($minutes)
    {
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        return sprintf('%d jam %d menit', $hours, $mins);
    }

    private function styleExcel($sheet, $lastRow)
    {
        // Title styling
        $sheet->mergeCells('A1:G1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Period styling
        $sheet->mergeCells('A2:G2');
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Header styling
        $sheet->getStyle('A4:G4')->getFont()->setBold(true);
        $sheet->getStyle('A4:G4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('D9EAD3');
        $sheet->getStyle('A4:G4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Data styling
        $sheet->getStyle('A4:G' . $lastRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        // Auto size columns
        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }
}