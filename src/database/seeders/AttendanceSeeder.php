<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\CorrectionRequest;

class AttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $users = User::where('is_admin', false)->get();

        foreach ($users as $user) {
            $attendance = Attendance::create([
                'user_id' => $user->id,
                'work_date' => '2026-04-01',
                'clock_in' => '2026-04-01 09:00:00',
                'clock_out' => '2026-04-01 18:00:00',
                'status' => 'off_duty',
                'note' => null,
            ]);

            BreakTime::create([
                'attendance_id' => $attendance->id,
                'break_start' => '2026-04-01 12:00:00',
                'break_end' => '2026-04-01 13:00:00',
            ]);

            // ② 退勤なし（異常系）
            $attendance2 = Attendance::create([
                'user_id' => $user->id,
                'work_date' => '2026-04-02',
                'clock_in' => '2026-04-02 09:00:00',
                'clock_out' => null,
                'status' => 'working',
                'note' => '退勤打刻なしのテストデータ'
            ]);

            // ③ 休憩なし
            $attendance3 = Attendance::create([
                'user_id' => $user->id,
                'work_date' => '2026-04-03',
                'clock_in' => '2026-04-03 09:00:00',
                'clock_out' => '2026-04-03 18:00:00',
                'status' => 'off_duty',
                'note' => '休憩なしのテストデータ'
            ]);

            $attendance4 = Attendance::create([
                'user_id' => $user->id,
                'work_date' => '2026-04-04',
                'clock_in' => '2026-04-04 09:00:00',
                'clock_out' => '2026-04-04 18:00:00',
                'status' => 'off_duty',
                'note' => '修正申請ありのテストデータ',
            ]);

            BreakTime::create([
                'attendance_id' => $attendance4->id,
                'break_start' => '2026-04-04 12:00:00',
                'break_end' => '2026-04-04 13:00:00',
            ]);

            CorrectionRequest::create([
                'attendance_id' => $attendance4->id,
                'user_id' => $user->id,
                'requested_clock_in' => '09:30:00',
                'requested_clock_out' => '18:30:00',
                'requested_breaks' => [
                    [
                        'break_start' => '12:00',
                        'break_end' => '13:00',
                    ],
                ],
                'note' => '出勤時間と退勤時間の修正申請',
                'status' => 'pending',
            ]);
        }

        
    }
}
