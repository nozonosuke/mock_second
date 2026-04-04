<?php

namespace App\Http\Controllers;

//use Illuminate\Http\Request;
use App\Models\Attendance;
//use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\AttendanceDetailRequest;
use App\Models\CorrectionRequest;

class AttendanceDetailController extends Controller
{
    public function show($id)
    {
        $attendance = Attendance::with(['user', 'breakTimes', 'correctionRequests'])->findOrFail($id);

        if ($attendance->user_id !== Auth::id()) {
            abort(403);
        }

        $breakTimes = $attendance->breakTimes->values();

        // 追加1行分
        $breakTimes[] = (object)[
            'break_start' => null,
            'break_end' => null,
        ];

        $isPending = $attendance->correctionRequests()
            ->where('status', 'pending')
            ->exists();

        return view('attendance.detail', compact('attendance', 'breakTimes', 'isPending'));
    }

    public function requestCorrection(AttendanceDetailRequest $request, $id)
    {
        $attendance = Attendance::findOrFail($id);

        if ($attendance->user_id !== Auth::id()) {
            abort(403);
        }

        $isPending = $attendance->correctionRequests()
            ->where('status', 'pending')
            ->exists();

        if ($isPending) {
            return back()->with('message', '承認待ちのため修正はできません。');
        }

        $breaks = collect($request->input('breaks', []))
            ->filter(function ($break) {
                return !empty($break['break_start']) && !empty($break['break_end']);
            })
            ->values()
            ->toArray();

        CorrectionRequest::create([
            'attendance_id' => $attendance->id,
            'user_id' => Auth::id(),
            'requested_clock_in' => $request->clock_in,
            'requested_clock_out' => $request->clock_out,
            'requested_breaks' => $breaks,
            'note' => $request->note,
            'status' => 'pending',
        ]);

        return back()->with('message', '修正申請を送信しました。');
    }
}
