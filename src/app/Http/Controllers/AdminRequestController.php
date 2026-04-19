<?php

namespace App\Http\Controllers;

use App\Models\CorrectionRequest;
use Illuminate\Http\Request;
use App\Models\BreakTime;
use Carbon\Carbon;

class AdminRequestController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'pending');

        $requests = CorrectionRequest::with(['user', 'attendance'])
            ->where('status', $status)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.request.list', compact('requests', 'status'));
    }

    public function show(CorrectionRequest $correctionRequest)
    {
        $correctionRequest->load(['user', 'attendance', 'attendance.breakTimes']);

        $requestedBreaks = $correctionRequest->requested_breaks ?? [];

        return view('admin.request.detail', compact('correctionRequest', 'requestedBreaks'));
    }

    public function approve(CorrectionRequest $correctionRequest)
    {
        if ($correctionRequest->status === 'approved') {
            return redirect()
                ->route('admin.request.show', $correctionRequest->id)
                ->with('success', 'すでに承認済みです。');
        }

        $correctionRequest->load(['attendance', 'attendance.breakTimes']);

        $attendance = $correctionRequest->attendance;
        $workDate = Carbon::parse($attendance->work_date)->format('Y-m-d');

        $attendance->update([
            'clock_in' => Carbon::parse($workDate . ' ' . $correctionRequest->requested_clock_in),
            'clock_out' => Carbon::parse($workDate . ' ' . $correctionRequest->requested_clock_out),
            'note' => $correctionRequest->note,
        ]);

        BreakTime::where('attendance_id', $attendance->id)->delete();

        $requestedBreaks = $correctionRequest->requested_breaks ?? [];

        foreach ($requestedBreaks as $break) {
            $breakStart = $break['break_start'] ?? null;
            $breakEnd = $break['break_end'] ?? null;

            if (empty($breakStart) && empty($breakEnd)) {
                continue;
            }

            BreakTime::create([
                'attendance_id' => $attendance->id,
                'break_start' => $breakStart ? Carbon::parse($workDate . ' ' . $breakStart) : null,
                'break_end' => $breakEnd ? Carbon::parse($workDate . ' ' . $breakEnd) : null,
            ]);
        }

        $correctionRequest->update([
            'status' => 'approved',
        ]);

        return redirect()
            ->route('admin.request.show', $correctionRequest->id)
            ->with('success', '申請を承認しました。');
    }
}
