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
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $department_id = $request->get('department_id');
        $category_filter = $request->get('category_filter', 'all'); // ✅ TAMBAHAN: Default 'all'
        $status_filter = $request->get('status_filter', 'completed');

        // Build query untuk overtime leaderboard
        $query = Employee::with(['department', 'jobLevel'])
            ->whereHas('overtimeDetails', function ($q) use ($startDate, $endDate, $category_filter, $status_filter) {
                $q->where('is_rejected', false);
                
                $q->whereHas('overtimeRequest', function ($subQ) use ($startDate, $endDate, $category_filter, $status_filter) {
                    // ✅ TAMBAHAN: Filter berdasarkan category
                    if ($category_filter !== 'all') {
                        $subQ->where('overtime_category', $category_filter);
                    }
                    
                    // Filter berdasarkan status
                    if ($status_filter === 'completed') {
                        $subQ->where('status', 'completed');
                    } elseif ($status_filter === 'in_progress') {
                        $subQ->whereIn('status', ['approved_sect', 'approved_subdept', 'approved_dept', 'approved_subdiv', 'approved_div', 'pending']);
                    } elseif ($status_filter === 'realisasi') {
                        $subQ->where('status', 'approved');
                    } elseif ($status_filter === 'all') {
                        // Tidak ada filter status khusus
                    }
                    
                    // Filter berdasarkan tanggal
                    if ($startDate) {
                        $subQ->where('date', '>=', $startDate);
                    }
                    if ($endDate) {
                        $subQ->where('date', '<=', $endDate);
                    }
                });
            });

        if ($department_id) {
            $query->where('department_id', $department_id);
        }

        // Get employees dengan total overtime hours
        $employees = $query->get()->map(function ($employee) use ($startDate, $endDate, $category_filter, $status_filter) {
            $overtimeDetailsQuery = $employee->overtimeDetails()
                ->where('is_rejected', false)
                ->whereHas('overtimeRequest', function ($q) use ($startDate, $endDate, $category_filter, $status_filter) {
                    // ✅ TAMBAHAN: Filter berdasarkan category
                    if ($category_filter !== 'all') {
                        $q->where('overtime_category', $category_filter);
                    }
                    
                    // Filter berdasarkan status
                    if ($status_filter === 'completed') {
                        $q->where('status', 'completed');
                    } elseif ($status_filter === 'in_progress') {
                        $q->whereIn('status', ['approved_sect', 'approved_subdept', 'approved_dept', 'approved_subdiv', 'approved_div', 'pending']);
                    } elseif ($status_filter === 'realisasi') {
                        $q->where('status', 'approved');
                    }
                    
                    // Filter berdasarkan tanggal
                    if ($startDate) {
                        $q->where('date', '>=', $startDate);
                    }
                    if ($endDate) {
                        $q->where('date', '<=', $endDate);
                    }
                });

            $overtimeDetails = $overtimeDetailsQuery->get();

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
        })->filter(function ($employee) {
            return $employee->total_hours > 0;
        })->sortByDesc('total_hours')->values();

        // Get departments untuk filter
        $departments = \App\Models\Department::orderBy('name')->get();

        if ($request->ajax()) {
            return response()->json([
                'data' => $employees
            ]);
        }

        return view('reports.overtime-leaderboard', compact(
            'employees', 
            'departments', 
            'startDate', 
            'endDate', 
            'department_id',
            'category_filter', // ✅ TAMBAHAN
            'status_filter'
        ));
    }

    public function getEmployeeDetails(Request $request, $employeeId)
    {
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $category_filter = $request->get('category_filter', 'all'); // ✅ TAMBAHAN
        $status_filter = $request->get('status_filter', 'completed');

        $employee = Employee::with(['department', 'jobLevel'])->findOrFail($employeeId);

        $details = OvertimeDetail::with(['overtimeRequest' => function($q) {
                $q->select('id', 'request_number', 'date', 'status', 'overtime_category'); // ✅ TAMBAHAN: Include overtime_category
            }, 'processType'])
            ->where('employee_id', $employeeId)
            ->where('is_rejected', false)
            ->whereHas('overtimeRequest', function ($q) use ($startDate, $endDate, $category_filter, $status_filter) {
                // ✅ TAMBAHAN: Filter berdasarkan category
                if ($category_filter !== 'all') {
                    $q->where('overtime_category', $category_filter);
                }
                
                // Filter berdasarkan status
                if ($status_filter === 'completed') {
                    $q->where('status', 'completed');
                } elseif ($status_filter === 'in_progress') {
                    $q->whereIn('status', ['approved_sect', 'approved_subdept', 'approved_dept', 'approved_subdiv', 'approved_div', 'pending']);
                } elseif ($status_filter === 'realisasi') {
                    $q->where('status', 'approved');
                }
                
                // Filter berdasarkan tanggal
                if ($startDate) {
                    $q->where('date', '>=', $startDate);
                }
                if ($endDate) {
                    $q->where('date', '<=', $endDate);
                }
            })
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($detail) {
                $startTime = Carbon::parse($detail->start_time);
                $endTime = Carbon::parse($detail->end_time);
                $duration = $endTime->diffInMinutes($startTime);

                return [
                    'spk_number' => $detail->overtimeRequest->request_number,
                    'category' => $detail->overtimeRequest->overtime_category, // ✅ TAMBAHAN
                    'date' => Carbon::parse($detail->overtimeRequest->date)->format('d/m/Y'),
                    'start_time' => $detail->start_time,
                    'end_time' => $detail->end_time,
                    'duration_minutes' => $duration,
                    'formatted_duration' => $this->formatMinutesToHours($duration),
                    'work_priority' => $detail->work_priority,
                    'work_process' => $detail->processType ? $detail->processType->name : $detail->work_process,
                    'status' => $detail->overtimeRequest->status
                ];
            });

        return response()->json([
            'employee' => $employee,
            'details' => $details
        ]);
    }

    public function exportExcel(Request $request)
    {
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $department_id = $request->get('department_id');
        $category_filter = $request->get('category_filter', 'all'); // ✅ TAMBAHAN
        $status_filter = $request->get('status_filter', 'completed');

        // Get the same data as index method
        $query = Employee::with(['department', 'jobLevel'])
            ->whereHas('overtimeDetails', function ($q) use ($startDate, $endDate, $category_filter, $status_filter) {
                $q->where('is_rejected', false);
                
                $q->whereHas('overtimeRequest', function ($subQ) use ($startDate, $endDate, $category_filter, $status_filter) {
                    // ✅ TAMBAHAN: Filter berdasarkan category
                    if ($category_filter !== 'all') {
                        $subQ->where('overtime_category', $category_filter);
                    }
                    
                    // Filter berdasarkan status
                    if ($status_filter === 'completed') {
                        $subQ->where('status', 'completed');
                    } elseif ($status_filter === 'in_progress') {
                        $subQ->whereIn('status', ['approved_sect', 'approved_subdept', 'approved_dept', 'approved_subdiv', 'approved_div', 'pending']);
                    } elseif ($status_filter === 'realisasi') {
                        $subQ->where('status', 'approved');
                    }
                    
                    // Filter berdasarkan tanggal
                    if ($startDate) {
                        $subQ->where('date', '>=', $startDate);
                    }
                    if ($endDate) {
                        $subQ->where('date', '<=', $endDate);
                    }
                });
            });

        if ($department_id) {
            $query->where('department_id', $department_id);
        }

        $employees = $query->get()->map(function ($employee) use ($startDate, $endDate, $category_filter, $status_filter) {
            $overtimeDetailsQuery = $employee->overtimeDetails()
                ->where('is_rejected', false)
                ->whereHas('overtimeRequest', function ($q) use ($startDate, $endDate, $category_filter, $status_filter) {
                    // ✅ TAMBAHAN: Filter berdasarkan category
                    if ($category_filter !== 'all') {
                        $q->where('overtime_category', $category_filter);
                    }
                    
                    // Filter berdasarkan status
                    if ($status_filter === 'completed') {
                        $q->where('status', 'completed');
                    } elseif ($status_filter === 'in_progress') {
                        $q->whereIn('status', ['approved_sect', 'approved_subdept', 'approved_dept', 'approved_subdiv', 'approved_div', 'pending']);
                    } elseif ($status_filter === 'realisasi') {
                        $q->where('status', 'approved');
                    }
                    
                    if ($startDate) {
                        $q->where('date', '>=', $startDate);
                    }
                    if ($endDate) {
                        $q->where('date', '<=', $endDate);
                    }
                });

            $overtimeDetails = $overtimeDetailsQuery->get();

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
        })->filter(function ($employee) {
            return $employee->total_hours > 0;
        })->sortByDesc('total_hours')->values();

        // Create Excel file
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set title
        $periodText = '';
        if ($startDate && $endDate) {
            $periodText = Carbon::parse($startDate)->format('d/m/Y') . ' - ' . Carbon::parse($endDate)->format('d/m/Y');
        } elseif ($startDate) {
            $periodText = 'Sejak ' . Carbon::parse($startDate)->format('d/m/Y');
        } elseif ($endDate) {
            $periodText = 'Sampai ' . Carbon::parse($endDate)->format('d/m/Y');
        } else {
            $periodText = 'Semua Periode';
        }

        // ✅ TAMBAHAN: Category text
        $categoryText = '';
        switch ($category_filter) {
            case 'planned':
                $categoryText = 'Planning';
                break;
            case 'unplanned':
                $categoryText = 'Unplanned';
                break;
            default:
                $categoryText = 'Semua Jenis';
        }

        $statusText = '';
        switch ($status_filter) {
            case 'completed':
                $statusText = 'Completed';
                break;
            case 'in_progress':
                $statusText = 'In Progress';
                break;
            case 'realisasi':
                $statusText = 'Realisasi';
                break;
            default:
                $statusText = 'Semua Status';
        }

        $sheet->setTitle('Overtime Leaderboard');
        $sheet->setCellValue('A1', 'LAPORAN LEADERBOARD LEMBUR');
        $sheet->setCellValue('A2', 'Periode: ' . $periodText);
        $sheet->setCellValue('A3', 'Jenis Lembur: ' . $categoryText); // ✅ TAMBAHAN
        $sheet->setCellValue('A4', 'Status: ' . $statusText);
        $sheet->setCellValue('A5', 'Catatan: Detail lembur yang ditolak (rejected) tidak dihitung dalam laporan ini');

        // Set headers (row 7 sekarang)
        $headers = ['No', 'ID Karyawan', 'Nama Karyawan', 'Department', 'Level Jabatan', 'Total Jam', 'Total Pengajuan'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '7', $header);
            $col++;
        }

        // Fill data (mulai dari row 8)
        $row = 8;
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
        $filename = 'overtime_leaderboard_' . $categoryText . '_' . $statusText . '_' . date('YmdHis') . '.xlsx';
        
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

        // ✅ TAMBAHAN: Category styling
        $sheet->mergeCells('A3:G3');
        $sheet->getStyle('A3')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A3')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E3F2FD');

        // Status styling
        $sheet->mergeCells('A4:G4');
        $sheet->getStyle('A4')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Note styling
        $sheet->mergeCells('A5:G5');
        $sheet->getStyle('A5')->getFont()->setItalic(true)->setSize(10);
        $sheet->getStyle('A5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A5')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFF9C4');

        // Header styling (row 7 sekarang)
        $sheet->getStyle('A7:G7')->getFont()->setBold(true);
        $sheet->getStyle('A7:G7')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('D9EAD3');
        $sheet->getStyle('A7:G7')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Data styling
        $sheet->getStyle('A7:G' . $lastRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        // Auto size columns
        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }
}