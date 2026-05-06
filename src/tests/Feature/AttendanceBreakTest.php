<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceBreakTest extends TestCase
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

    private function createWorkingAttendance($user)
    {
        return Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-04-01',
            'clock_in' => '2026-04-01 09:00:00',
            'clock_out' => null,
            'status' => 'working',
        ]);
    }

    public function test_break_start_button_works()
    {
        Carbon::setTestNow(Carbon::parse('2026-04-01 12:00:00'));

        $user = $this->createVerifiedUser();
        $this->createWorkingAttendance($user);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
        $response->assertSee('休憩入');

        $this->actingAs($user)->post('/attendance/break-start');

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
        $response->assertSee('休憩中');

        Carbon::setTestNow();
    }

    public function test_user_can_take_break_multiple_times_a_day()
    {
        Carbon::setTestNow(Carbon::parse('2026-04-01 12:00:00'));

        $user = $this->createVerifiedUser();
        $this->createWorkingAttendance($user);

        $this->actingAs($user)->post('/attendance/break-start');

        Carbon::setTestNow(Carbon::parse('2026-04-01 13:00:00'));

        $this->actingAs($user)->post('/attendance/break-end');

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
        $response->assertSee('休憩入');

        Carbon::setTestNow();
    }

    public function test_break_end_button_works()
    {
        Carbon::setTestNow(Carbon::parse('2026-04-01 12:00:00'));

        $user = $this->createVerifiedUser();
        $this->createWorkingAttendance($user);

        $this->actingAs($user)->post('/attendance/break-start');

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
        $response->assertSee('休憩戻');

        Carbon::setTestNow(Carbon::parse('2026-04-01 13:00:00'));

        $this->actingAs($user)->post('/attendance/break-end');

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
        $response->assertSee('出勤中');

        Carbon::setTestNow();
    }

    public function test_user_can_return_from_break_multiple_times_a_day()
    {
        Carbon::setTestNow(Carbon::parse('2026-04-01 12:00:00'));

        $user = $this->createVerifiedUser();
        $this->createWorkingAttendance($user);

        $this->actingAs($user)->post('/attendance/break-start');

        Carbon::setTestNow(Carbon::parse('2026-04-01 13:00:00'));

        $this->actingAs($user)->post('/attendance/break-end');

        Carbon::setTestNow(Carbon::parse('2026-04-01 15:00:00'));

        $this->actingAs($user)->post('/attendance/break-start');

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
        $response->assertSee('休憩戻');

        Carbon::setTestNow();
    }

    public function test_break_time_is_displayed_on_attendance_list()
    {
        Carbon::setTestNow(Carbon::parse('2026-04-01 12:00:00'));

        $user = $this->createVerifiedUser();
        $this->createWorkingAttendance($user);

        $this->actingAs($user)->post('/attendance/break-start');

        Carbon::setTestNow(Carbon::parse('2026-04-01 13:00:00'));

        $this->actingAs($user)->post('/attendance/break-end');

        $this->assertDatabaseHas('break_times', [
            'break_start' => '2026-04-01 12:00:00',
            'break_end' => '2026-04-01 13:00:00',
        ]);

        $response = $this->actingAs($user)->get('/attendance/list');

        $response->assertStatus(200);

        $response->assertSee('1:00');

        Carbon::setTestNow();
    }
}