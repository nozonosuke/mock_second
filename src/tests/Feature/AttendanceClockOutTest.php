<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceClockOutTest extends TestCase
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

    public function test_clock_out_button_works()
    {
        Carbon::setTestNow(Carbon::parse('2026-04-01 18:00:00'));

        $user = $this->createVerifiedUser();

        $this->createWorkingAttendance($user);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
        $response->assertSee('退勤');

        $this->actingAs($user)->post('/attendance/clock-out');

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
        $response->assertSee('退勤済');

        Carbon::setTestNow();
    }

    public function test_clock_out_time_is_displayed_on_attendance_list()
    {
        $user = $this->createVerifiedUser();

        Carbon::setTestNow(Carbon::parse('2026-04-01 09:00:00'));

        $this->actingAs($user)->post('/attendance/clock-in');

        Carbon::setTestNow(Carbon::parse('2026-04-01 18:00:00'));

        $this->actingAs($user)->post('/attendance/clock-out');

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'work_date' => '2026-04-01',
            'clock_in' => '2026-04-01 09:00:00',
            'clock_out' => '2026-04-01 18:00:00',
            'status' => 'done',
        ]);

        $response = $this->actingAs($user)->get('/attendance/list');

        $response->assertStatus(200);
        $response->assertSee('18:00');

        Carbon::setTestNow();
    }
}
