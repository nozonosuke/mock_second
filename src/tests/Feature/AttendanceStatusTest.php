<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceStatusTest extends TestCase
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

    public function test_status_is_off_duty()
    {
        Carbon::setTestNow(Carbon::parse('2026-04-01 09:00:00'));

        $user = $this->createVerifiedUser();

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
        $response->assertSee('勤務外');

        Carbon::setTestNow();
    }

    public function test_status_is_working()
    {
        Carbon::setTestNow(Carbon::parse('2026-04-01 09:00:00'));

        $user = $this->createVerifiedUser();

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-04-01',
            'clock_in' => '2026-04-01 09:00:00',
            'clock_out' => null,
            'status' => 'working',
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
        $response->assertSee('出勤中');

        Carbon::setTestNow();
    }

    public function test_status_is_on_break()
    {
        Carbon::setTestNow(Carbon::parse('2026-04-01 09:00:00'));

        $user = $this->createVerifiedUser();

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-04-01',
            'clock_in' => '2026-04-01 09:00:00',
            'clock_out' => null,
            'status' => 'on_break',
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);
        $response->assertSee('休憩中');

        Carbon::setTestNow();
    }

    public function test_status_is_done()
    {
        Carbon::setTestNow(Carbon::parse('2026-04-01 09:00:00'));

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

        Carbon::setTestNow();
    }
}
