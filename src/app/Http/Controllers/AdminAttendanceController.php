<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use Carbon\Carbon;
use App\Models\User;

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

        return view('admin.attendance_list', [
            'attendanceList' => $attendanceList,
            'currentDate' => $date,
            'prevDate' => $date->copy()->subDay()->toDateString(),
            'nextDate' => $date->copy()->addDay()->toDateString(),
        ]);
    }

    public function show(Attendance $attendance)
    {
        return view('admin.attendance_detail', compact('attendance'));
    }
}
