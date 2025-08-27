<?php

namespace App\Http\Controllers;

use App\Models\OvertimeRequest;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $totalUsers = User::where('is_active', true)->count();
        $totalRequests = OvertimeRequest::count();
        $pendingRequests = OvertimeRequest::where('status', 'pending')->count();
        $completedRequests = OvertimeRequest::where('status', 'completed')->count();

        // Ambil data OvertimeRequest terbaru, misal 10 terbaru, dengan pagination
        $requests = OvertimeRequest::latest()->paginate(10);

        return view('dashboard', compact(
            'totalUsers',
            'totalRequests',
            'pendingRequests',
            'completedRequests',
            'requests' // <-- tambahkan ini supaya Blade bisa pakai $requests
        ));
    }
}
