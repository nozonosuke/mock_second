<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    private function createAdminUser()
    {
        return User::create([
            'name' => '管理者ユーザー',
            'email' => uniqid() . '@example.com',
            'password' => bcrypt('password123'),
            'is_admin' => true,
            'email_verified_at' => Carbon::now(),
        ]);
    }

    private function createUser()
    {
        return User::create([
            'name' => '山田太郎',
            'email' => uniqid() . '@example.com',
            'password' => bcrypt('password123'),
            'is_admin' => false,
            'email_verified_at' => Carbon::now(),
        ]);
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

    public function test_admin_can_see_selected_attendance_detail()
    {
        $admin = $this->createAdminUser();
        $user = $this->createUser();
        $attendance = $this->createAttendanceWithBreak($user);

        $response = $this->actingAs($admin)->get('/admin/attendance/' . $attendance->id);

        $response->assertStatus(200);
        $response->assertSee('山田太郎');
        $response->assertSee('2026');
        $response->assertSee('4月1日');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('12:00');
        $response->assertSee('13:00');
    }

    public function test_clock_in_after_clock_out_shows_error_for_admin()
    {
        $admin = $this->createAdminUser();
        $user = $this->createUser();
        $attendance = $this->createAttendanceWithBreak($user);

        $response = $this->actingAs($admin)->post('/admin/attendance/' . $attendance->id, [
            'clock_in' => '19:00',
            'clock_out' => '18:00',
            'breaks' => [
                [
                    'break_start' => '12:00',
                    'break_end' => '13:00',
                ],
            ],
            'note' => '管理者による修正です',
        ]);

        $response->assertSessionHasErrors([
            'clock_in' => '出勤時間もしくは退勤時間が不適切な値です',
        ]);
    }

    public function test_break_start_after_clock_out_shows_error_for_admin()
    {
        $admin = $this->createAdminUser();
        $user = $this->createUser();
        $attendance = $this->createAttendanceWithBreak($user);

        $response = $this->actingAs($admin)->post('/admin/attendance/' . $attendance->id, [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'breaks' => [
                1 => [
                    'break_start' => '19:00',
                    'break_end' => '20:00',
                ],
            ],
            'note' => '管理者による修正です',
        ]);

        $response->assertSessionHasErrors([
            'breaks' => '休憩時間もしくは退勤時間が不適切な値です',
        ]);
    }

    public function test_break_end_after_clock_out_shows_error_for_admin()
    {
        $admin = $this->createAdminUser();
        $user = $this->createUser();
        $attendance = $this->createAttendanceWithBreak($user);

        $response = $this->actingAs($admin)->post('/admin/attendance/' . $attendance->id, [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'breaks' => [
                1 => [
                    'break_start' => '17:00',
                    'break_end' => '19:00',
                ],
            ],
            'note' => '管理者による修正です',
        ]);

        $response->assertSessionHasErrors([
            'breaks' => '休憩時間もしくは退勤時間が不適切な値です',
        ]);
    }

    public function test_note_is_required_for_admin()
    {
        $admin = $this->createAdminUser();
        $user = $this->createUser();
        $attendance = $this->createAttendanceWithBreak($user);

        $response = $this->actingAs($admin)->post('/admin/attendance/' . $attendance->id, [
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
}