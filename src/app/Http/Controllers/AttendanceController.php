<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Attendance;
use Carbon\Carbon;
use App\Models\BreakTime;

class AttendanceController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $now = Carbon::now();
        $today = Carbon::today();

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('work_date', $today)
            ->first();

        $status = $attendance?->status ?? 'off_duty';

        $statusLabels = [
            'off_duty' => '勤務外',
            'working' => '出勤中',
            'on_break' => '休憩中',
            'done' => '退勤済',
        ];

        $statusLabel = $statusLabels[$status];

        $currentDate = $now->isoFormat('YYYY年M月D日(ddd)');
        $currentTime = $now->format('H:i');

        return view('attendance.index', compact(
            'attendance',
            'status',
            'statusLabel',
            'currentDate',
            'currentTime'
        ));
    }

    public function clockIn()
    {
        $user = Auth::user();
        $today = Carbon::today();
        $now = Carbon::now();

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('work_date', $today)
            ->first();

        if ($attendance) {
            return redirect()->route('attendance.index')->with('message', '本日はすでに出勤済みです。');
        }

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => $today->toDateString(),
            'clock_in' => $now,
            'status' => 'working',
        ]);

        return redirect()->route('attendance.index')->with('message', '出勤を記録しました。');
    }

    public function breakStart()
    {
        $user = Auth::user();
        $today = Carbon::today();
        $now = Carbon::now();

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('work_date', $today)
            ->first();

        if (!$attendance || $attendance->status !== 'working') {
            return redirect()->route('attendance.index')->with('message', '休憩入は出勤中のみ可能です。');
        }

        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start' => $now,
        ]);

        $attendance->update([
            'status' => 'on_break',
        ]);

        return redirect()->route('attendance.index')->with('message', '休憩に入りました。');
    }

    public function breakEnd()
    {
        $user = Auth::user();
        $today = Carbon::today();
        $now = Carbon::now();

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('work_date', $today)
            ->first();

        if (!$attendance || $attendance->status !== 'on_break') {
            return redirect()->route('attendance.index')->with('message', '休憩戻は休憩中のみ可能です。');
        }

        $breakTime = BreakTime::where('attendance_id', $attendance->id)
            ->whereNull('break_end')
            ->latest()
            ->first();

        if (!$breakTime) {
            return redirect()->route('attendance.index')->with('message', '未終了の休憩記録が見つかりません。');
        }

        $breakTime->update([
            'break_end' => $now,
        ]);

        $attendance->update([
            'status' => 'working',
        ]);

        return redirect()->route('attendance.index')->with('message', '休憩から戻りました。');
    }

    public function clockOut()
    {
        $user = Auth::user();
        $today = Carbon::today();
        $now = Carbon::now();

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('work_date', $today)
            ->first();

        if (!$attendance || $attendance->status !== 'working') {
            return redirect()->route('attendance.index')->with('message', '退勤は出勤中のみ可能です。');
        }

        $attendance->update([
            'clock_out' => $now,
            'status' => 'done',
        ]);

        return redirect()->route('attendance.index')->with('message', 'お疲れ様でした。');
    }

    public function list(Request $request)
    {
        $user = Auth::user();

        $month = $request->filled('month')
            ? Carbon::createFromFormat('Y-m', $request->month)->startOfMonth()
            : Carbon::now()->startOfMonth();

        $startOfMonth = $month->copy()->startOfMonth();
        $endOfMonth = $month->copy()->endOfMonth();

        $attendances = Attendance::with('breakTimes')
            ->where('user_id', $user->id)
            ->whereBetween('work_date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
            ->orderBy('work_date', 'asc')
            ->get()
            ->keyBy(function ($attendance) {
                return Carbon::parse($attendance->work_date)->format('Y-m-d');
            });

        $weekLabels = ['日', '月', '火', '水', '木', '金', '土'];
        $rows = [];

        for ($date = $startOfMonth->copy(); $date->lte($endOfMonth); $date->addDay()) {
            $dateKey = $date->format('Y-m-d');
            $attendance = $attendances->get($dateKey);

            $clockIn = $attendance && $attendance->clock_in
                ? Carbon::parse($attendance->clock_in)->format('H:i')
                : '';

            $clockOut = $attendance && $attendance->clock_out
                ? Carbon::parse($attendance->clock_out)->format('H:i')
                : '';

            $breakMinutes = 0;

            if ($attendance) {
                foreach ($attendance->breakTimes as $breakTime) {
                    if ($breakTime->break_start && $breakTime->break_end) {
                        $breakMinutes += Carbon::parse($breakTime->break_start)
                            ->diffInMinutes(Carbon::parse($breakTime->break_end));
                    }
                }
            }

            $breakTotal = $breakMinutes > 0
                ? sprintf('%d:%02d', floor($breakMinutes / 60), $breakMinutes % 60)
                : '';

            $workTotal = '';
            if ($attendance && $attendance->clock_in && $attendance->clock_out) {
                $workMinutes = Carbon::parse($attendance->clock_in)
                    ->diffInMinutes(Carbon::parse($attendance->clock_out)) - $breakMinutes;

                $workMinutes = max($workMinutes, 0);

                $workTotal = sprintf('%d:%02d', floor($workMinutes / 60), $workMinutes % 60);
            }

            $rows[] = [
                'id' => $attendance ? $attendance->id : null,
                'date' => $dateKey,
                'date_label' => $date->format('m/d') . '(' . $weekLabels[$date->dayOfWeek] . ')',
                'clock_in' => $clockIn,
                'clock_out' => $clockOut,
                'break_total' => $breakTotal,
                'work_total' => $workTotal,
            ];
        }

        $displayMonth = $month->format('Y/m');
        $prevMonth = $month->copy()->subMonth()->format('Y-m');
        $nextMonth = $month->copy()->addMonth()->format('Y-m');

        return view('attendance.list', compact(
            'rows',
            'displayMonth',
            'prevMonth',
            'nextMonth'
        ));
    }
}
