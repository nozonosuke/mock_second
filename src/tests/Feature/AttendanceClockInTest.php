<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceClockInTest extends TestCase
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

    public function test_clock_in_button_works()
    {
        Carbon::setTestNow(Carbon::parse('2026-04-01 09:00:00'));

        $user = $this->createVerifiedUser();

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
        $response->assertSee('出勤');

        $this->actingAs($user)->post('/attendance/clock-in');

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
        $response->assertSee('出勤中');

        Carbon::setTestNow();
    }

    public function test_user_cannot_clock_in_more_than_once_a_day()
    {
        Carbon::setTestNow(Carbon::parse('2026-04-01 18:00:00'));

        $user = $this->createVerifiedUser();

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-04-01',
            'clock_in' => '2026-04-01 09:00:00',
            'clock_out' => '2026-04-01 18:00:00',
            'status' => 'done',
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
        $response->assertSee('退勤済');
        $response->assertDontSee('出勤');

        Carbon::setTestNow();
    }

    public function test_clock_in_time_is_displayed_on_attendance_list()
    {
        Carbon::setTestNow(Carbon::parse('2026-04-01 09:00:00'));

        $user = $this->createVerifiedUser();

        $this->actingAs($user)->post('/attendance/clock-in');

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'work_date' => '2026-04-01',
            'clock_in' => '2026-04-01 09:00:00',
            'status' => 'working',
        ]);

        $response = $this->actingAs($user)->get('/attendance/list');

        $response->assertStatus(200);
        $response->assertSee('09:00');

        Carbon::setTestNow();
    }
}
