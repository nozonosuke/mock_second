<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceCorrectionTest extends TestCase
{
    use RefreshDatabase;

    private function createVerifiedUser()
    {
        $user = User::create([
            'name' => 'テストユーザー',
            'email' => uniqid() . '@example.com',
            'password' => bcrypt('password123'),
            'is_admin' => false,
        ]);

        $user->forceFill([
            'email_verified_at' => Carbon::now(),
        ])->save();

        return $user;
    }

    private function createAttendanceWithBreak($user)
    {
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-04-01',
            'clock_in' => '2026-04-01 09:00:00',
            'clock_out' => '2026-04-01 18:00:00',
            'status' => 'done',
        ]);

        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start' => '2026-04-01 12:00:00',
            'break_end' => '2026-04-01 13:00:00',
        ]);

        return $attendance;
    }

    public function test_clock_in_after_clock_out_shows_error()
    {
        $user = $this->createVerifiedUser();
        $attendance = $this->createAttendanceWithBreak($user);

        $response = $this->actingAs($user)->post('/attendance/detail/' . $attendance->id, [
            'clock_in' => '19:00',
            'clock_out' => '18:00',
            'breaks' => [
                [
                    'break_start' => '12:00',
                    'break_end' => '13:00',
                ],
            ],
            'note' => '修正理由です',
        ]);

        $response->assertSessionHasErrors([
            'clock_in' => '出勤時間もしくは退勤時間が不適切な値です',
        ]);
    }

    public function test_break_start_after_clock_out_shows_error()
    {
        $user = $this->createVerifiedUser();
        $attendance = $this->createAttendanceWithBreak($user);

        $response = $this->actingAs($user)->post('/attendance/detail/' . $attendance->id, [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'breaks' => [
                [
                    'break_start' => '19:00',
                    'break_end' => '20:00',
                ],
            ],
            'note' => '修正理由です',
        ]);

        $response->assertSessionHasErrors([
            'breaks.0.break_start' => '休憩時間が不適切な値です',
        ]);
    }

    public function test_break_end_after_clock_out_shows_error()
    {
        $user = $this->createVerifiedUser();
        $attendance = $this->createAttendanceWithBreak($user);

        $response = $this->actingAs($user)->post('/attendance/detail/' . $attendance->id, [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'breaks' => [
                [
                    'break_start' => '17:00',
                    'break_end' => '19:00',
                ],
            ],
            'note' => '修正理由です',
        ]);

        $response->assertSessionHasErrors([
            'breaks.0.break_end' => '休憩時間もしくは退勤時間が不適切な値です',
        ]);
    }

    public function test_note_is_required()
    {
        $user = $this->createVerifiedUser();
        $attendance = $this->createAttendanceWithBreak($user);

        $response = $this->actingAs($user)->post('/attendance/detail/' . $attendance->id, [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'breaks' => [
                [
                    'break_start' => '12:00',
                    'break_end' => '13:00',
                ],
            ],
            'note' => '',
        ]);

        $response->assertSessionHasErrors([
            'note' => '備考を記入してください',
        ]);
    }

    public function test_correction_request_is_created()
    {
        $user = $this->createVerifiedUser();
        $attendance = $this->createAttendanceWithBreak($user);

        $response = $this->actingAs($user)->post('/attendance/detail/' . $attendance->id, [
            'clock_in' => '10:00',
            'clock_out' => '19:00',
            'breaks' => [
                [
                    'break_start' => '13:00',
                    'break_end' => '14:00',
                ],
            ],
            'note' => '電車遅延のため修正申請します',
        ]);

        $this->assertDatabaseHas('correction_requests', [
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'requested_clock_in' => '10:00',
            'requested_clock_out' => '19:00',
            'note' => '電車遅延のため修正申請します',
            'status' => 'pending',
        ]);
    }

    public function test_correction_request_success_message_is_shown()
    {
        $user = $this->createVerifiedUser();
        $attendance = $this->createAttendanceWithBreak($user);

        $response = $this->actingAs($user)->post('/attendance/detail/' . $attendance->id, [
            'clock_in' => '10:00',
            'clock_out' => '19:00',
            'breaks' => [
                [
                    'break_start' => '13:00',
                    'break_end' => '14:00',
                ],
            ],
            'note' => '修正理由です',
        ]);

        $response->assertSessionHas('message', '修正申請を送信しました。');
    }

    public function test_cannot_request_correction_when_pending_request_exists()
    {
        $user = $this->createVerifiedUser();
        $attendance = $this->createAttendanceWithBreak($user);

        $this->actingAs($user)->post('/attendance/detail/' . $attendance->id, [
            'clock_in' => '10:00',
            'clock_out' => '19:00',
            'breaks' => [
                [
                    'break_start' => '13:00',
                    'break_end' => '14:00',
                ],
            ],
            'note' => '1回目の申請です',
        ]);

        $response = $this->actingAs($user)->post('/attendance/detail/' . $attendance->id, [
            'clock_in' => '11:00',
            'clock_out' => '20:00',
            'breaks' => [
                [
                    'break_start' => '14:00',
                    'break_end' => '15:00',
                ],
            ],
            'note' => '2回目の申請です',
        ]);

        $response->assertSessionHas('message', '承認待ちのため修正はできません。');

        $this->assertDatabaseCount('correction_requests', 1);
    }

    public function test_correction_request_is_displayed_on_request_list()
    {
        $user = $this->createVerifiedUser();
        $attendance = $this->createAttendanceWithBreak($user);

        $this->actingAs($user)->post('/attendance/detail/' . $attendance->id, [
            'clock_in' => '10:00',
            'clock_out' => '19:00',
            'breaks' => [
                [
                    'break_start' => '13:00',
                    'break_end' => '14:00',
                ],
            ],
            'note' => '申請一覧に表示されるテストです',
        ]);

        $response = $this->actingAs($user)->get('/stamp_correction_request/list');

        $response->assertStatus(200);
        $response->assertSee('テストユーザー');
        $response->assertSee('2026/04/01');
        $response->assertSee('申請一覧に表示されるテストです');
    }
}