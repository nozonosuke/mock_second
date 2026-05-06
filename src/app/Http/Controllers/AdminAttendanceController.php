<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use Carbon\Carbon;
use App\Models\User;
use App\Models\BreakTime;
use App\Http\Requests\AdminAttendanceUpdateRequest;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminAttendanceController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->query('date')
            ? Carbon::parse($request->query('date'))
            : Carbon::today();

        $users = User::with(['attendances' => function ($query) use ($date) {
            $query->whereDate('work_date', $date->toDateString())
                  ->with('breakTimes');
        }])->get();

        $attendanceList = $users->map(function ($user) use ($date) {
            $attendance = $user->attendances->first();

            $breakMinutes = 0;

            if ($attendance) {
                foreach ($attendance->breakTimes as $break) {
                    if ($break->break_start && $break->break_end) {
                        $breakMinutes += Carbon::parse($break->break_start)
                            ->diffInMinutes(Carbon::parse($break->break_end));
                    }
                }
            }

            $workMinutes = null;

            if ($attendance && $attendance->clock_in && $attendance->clock_out) {
                $totalMinutes = Carbon::parse($attendance->clock_in)
                    ->diffInMinutes(Carbon::parse($attendance->clock_out));

                $workMinutes = $totalMinutes - $breakMinutes;
            }

            return [
                'user_name' => $user->name,
                'attendance_id' => $attendance ? $attendance->id : null,
                'clock_in' => $attendance && $attendance->clock_in
                    ? Carbon::parse($attendance->clock_in)->format('H:i')
                    : '',
                'clock_out' => $attendance && $attendance->clock_out
                    ? Carbon::parse($attendance->clock_out)->format('H:i')
                    : '',
                'break_time' => $breakMinutes > 0
                    ? sprintf('%d:%02d', floor($breakMinutes / 60), $breakMinutes % 60)
                    : '',
                'total_time' => !is_null($workMinutes)
                    ? sprintf('%d:%02d', floor($workMinutes / 60), $workMinutes % 60)
                    : '',
            ];
        });

        return view('admin.attendance.list', [
            'attendanceList' => $attendanceList,
            'currentDate' => $date,
            'prevDate' => $date->copy()->subDay()->toDateString(),
            'nextDate' => $date->copy()->addDay()->toDateString(),
        ]);
    }

    public function show(Attendance $attendance)
    {
        $attendance->load(['user', 'breakTimes', 'correctionRequests']);

        $pendingRequest = $attendance->correctionRequests()
            ->where('status', 'pending')
            ->latest()
            ->first();

        $isPending = !is_null($pendingRequest);

        if ($pendingRequest && $pendingRequest->requested_breaks) {
            $breakTimes = collect($pendingRequest->requested_breaks)->map(function ($break) {
                return (object)[
                    'id' => null,
                    'break_start' => $break['break_start'] ?? null,
                    'break_end' => $break['break_end'] ?? null,
                ];
            });
        } else {
            $breakTimes = $attendance->breakTimes->values();
        }

        return view('admin.attendance.detail', compact(
            'attendance',
            'isPending',
            'pendingRequest',
            'breakTimes'
        ));
    }

    public function update(AdminAttendanceUpdateRequest $request, Attendance $attendance)
    {
        if ($attendance->correctionRequests()->where('status', 'pending')->exists()) {
            return redirect()
                ->route('admin.attendance.show', $attendance->id)
                ->with('error', '承認待ちのため修正はできません。');
        }

        $workDate = Carbon::parse($attendance->work_date)->format('Y-m-d');

        $attendance->update([
            'clock_in' => Carbon::parse($workDate . ' ' . $request->clock_in . ':00'),
            'clock_out' => Carbon::parse($workDate . ' ' . $request->clock_out . ':00'),
            'note' => $request->note,
        ]);

        if ($request->has('breaks')) {
            foreach ($request->breaks as $breakId => $break) {
                $breakStart = $break['break_start'] ?? null;
                $breakEnd = $break['break_end'] ?? null;

                if (empty($breakStart) && empty($breakEnd)) {
                    continue;
                }

                $breakStartDateTime = $breakStart
                    ? Carbon::parse($workDate . ' ' . $breakStart . ':00')
                    : null;

                $breakEndDateTime = $breakEnd
                    ? Carbon::parse($workDate . ' ' . $breakEnd . ':00')
                    : null;

                if (is_numeric($breakId)) {
                    $existingBreak = BreakTime::find($breakId);

                    if ($existingBreak) {
                        $existingBreak->update([
                            'break_start' => $breakStartDateTime,
                            'break_end' => $breakEndDateTime,
                        ]);
                    }
                } else {
                    BreakTime::create([
                        'attendance_id' => $attendance->id,
                        'break_start' => $breakStartDateTime,
                        'break_end' => $breakEndDateTime,
                    ]);
                }
            }
        }

        return redirect()
            ->route('admin.attendance.show', $attendance->id)
            ->with('success', '勤怠情報を修正しました');
    }

    public function staffList()
    {
        $users = User::where('is_admin', 0)
            ->orderBy('id')
            ->get();

        return view('admin.staff.list', compact('users'));
    }

    public function staffAttendance(Request $request, User $user)
    {
        $currentMonth = $request->query('month')
            ? Carbon::parse($request->query('month'))
            : Carbon::today();

        $startOfMonth = $currentMonth->copy()->startOfMonth();
        $endOfMonth = $currentMonth->copy()->endOfMonth();

        $attendances = Attendance::with('breakTimes')
            ->where('user_id', $user->id)
            ->whereBetween('work_date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
            ->get()
            ->keyBy(function ($attendance) {
                return Carbon::parse($attendance->work_date)->format('Y-m-d');
            });

        $attendanceList = [];

        $date = $startOfMonth->copy();

        while ($date->lte($endOfMonth)) {
            $attendance = $attendances->get($date->format('Y-m-d'));

            $breakMinutes = 0;
            $workMinutes = '';

            if ($attendance) {
                foreach ($attendance->breakTimes as $break) {
                    if ($break->break_start && $break->break_end) {
                        $breakMinutes += Carbon::parse($break->break_start)
                            ->diffInMinutes(Carbon::parse($break->break_end));
                    }
                }

                if ($attendance->clock_in && $attendance->clock_out) {
                    $totalMinutes = Carbon::parse($attendance->clock_in)
                        ->diffInMinutes(Carbon::parse($attendance->clock_out));

                    $workMinutes = $totalMinutes - $breakMinutes;
                }
            }

            $attendanceList[] = [
                'date' => $date->format('m/d') . '(' . ['日', '月', '火', '水', '木', '金', '土'][$date->dayOfWeek] . ')',
                'clock_in' => $attendance && $attendance->clock_in
                    ? Carbon::parse($attendance->clock_in)->format('H:i')
                    : '',
                'clock_out' => $attendance && $attendance->clock_out
                    ? Carbon::parse($attendance->clock_out)->format('H:i')
                    : '',
                'break_time' => $breakMinutes > 0
                    ? sprintf('%d:%02d', floor($breakMinutes / 60), $breakMinutes % 60)
                    : '',
                'total_time' => $workMinutes !== ''
                    ? sprintf('%d:%02d', floor($workMinutes / 60), $workMinutes % 60)
                    : '',
                'attendance_id' => $attendance ? $attendance->id : null,
            ];

            $date->addDay();
        }

        return view('admin.staff.attendance', [
            'user' => $user,
            'attendanceList' => $attendanceList,
            'currentMonth' => $currentMonth,
            'prevMonth' => $currentMonth->copy()->subMonth()->format('Y-m'),
            'nextMonth' => $currentMonth->copy()->addMonth()->format('Y-m'),
        ]);
    }

    public function exportStaffAttendanceCsv(Request $request, User $user)
    {
        $currentMonth = $request->query('month')
            ? Carbon::parse($request->query('month'))
            : Carbon::today();

        $startOfMonth = $currentMonth->copy()->startOfMonth();
        $endOfMonth = $currentMonth->copy()->endOfMonth();

        $attendances = Attendance::with('breakTimes')
            ->where('user_id', $user->id)
            ->whereBetween('work_date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
            ->get()
            ->keyBy(function ($attendance) {
                return Carbon::parse($attendance->work_date)->format('Y-m-d');
            });

        $fileName = $user->name . '_' . $currentMonth->format('Y_m') . '_attendance.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ];

        return response()->stream(function () use ($attendances, $startOfMonth, $endOfMonth) {
            $handle = fopen('php://output', 'w');

            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, ['日付', '出勤', '退勤', '休憩', '合計']);

            $date = $startOfMonth->copy();

            while ($date->lte($endOfMonth)) {
                $attendance = $attendances->get($date->format('Y-m-d'));

                $breakMinutes = 0;
                $workMinutes = '';

                if ($attendance) {
                    foreach ($attendance->breakTimes as $break) {
                        if ($break->break_start && $break->break_end) {
                            $breakMinutes += Carbon::parse($break->break_start)
                                ->diffInMinutes(Carbon::parse($break->break_end));
                        }
                    }

                    if ($attendance->clock_in && $attendance->clock_out) {
                        $totalMinutes = Carbon::parse($attendance->clock_in)
                            ->diffInMinutes(Carbon::parse($attendance->clock_out));

                        $workMinutes = $totalMinutes - $breakMinutes;
                    }
                }

                fputcsv($handle, [
                    $date->format('Y/m/d') . '(' . ['日', '月', '火', '水', '木', '金', '土'][$date->dayOfWeek] . ')',
                    $attendance && $attendance->clock_in
                        ? Carbon::parse($attendance->clock_in)->format('H:i')
                        : '',
                    $attendance && $attendance->clock_out
                        ? Carbon::parse($attendance->clock_out)->format('H:i')
                        : '',
                    $breakMinutes > 0
                        ? sprintf('%d:%02d', floor($breakMinutes / 60), $breakMinutes % 60)
                        : '',
                    $workMinutes !== ''
                        ? sprintf('%d:%02d', floor($workMinutes / 60), $workMinutes % 60)
                        : '',
                ]);

                $date->addDay();
            }

            fclose($handle);
        }, 200, $headers);
    }

}
