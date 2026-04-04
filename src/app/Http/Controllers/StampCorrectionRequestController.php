<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CorrectionRequest;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\StampCorrectionRequest;

class StampCorrectionRequestController extends Controller
{
    public function index()
    {
        $status = request('status', 'pending');

        $requests = CorrectionRequest::with(['user', 'attendance'])
            ->where('user_id', Auth::id())
            ->where('status', $status)
            ->latest()
            ->get();

        return view('attendance.request', compact('requests', 'status'));
    }

    public function store(StampCorrectionRequest $request)
    {
        CorrectionRequest::create([
            'user_id' => Auth::id(),
            'attendance_id' => $request->attendance_id,
            'requested_clock_in' => $request->clock_in,
            'requested_clock_out' => $request->clock_out,
            'note' => $request->note,
            'status' => 'pending',
        ]);

        return redirect()
            ->back()
            ->with('message', '修正申請を送信しました');
    }
}
