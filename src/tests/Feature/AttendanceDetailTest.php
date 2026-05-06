<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    private function createVerifiedUser()
    {
        $user = User::create([
            'name' => '山田 太郎',
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

    public function test_detail_page_displays_login_user_name()
    {
        $user = $this->createVerifiedUser();
        $attendance = $this->createAttendanceWithBreak($user);

        $response = $this->actingAs($user)->get('/attendance/detail/' . $attendance->id);

        $response->assertStatus(200);
        $response->assertSee('山田 太郎');
    }

    public function test_detail_page_displays_selected_date()
    {
        $user = $this->createVerifiedUser();
        $attendance = $this->createAttendanceWithBreak($user);

        $response = $this->actingAs($user)->get('/attendance/detail/' . $attendance->id);

        $response->assertStatus(200);
        $response->assertSee('2026年');
        $response->assertSee('4月1日');
    }

    public function test_detail_page_displays_clock_in_and_clock_out_time()
    {
        $user = $this->createVerifiedUser();
        $attendance = $this->createAttendanceWithBreak($user);

        $response = $this->actingAs($user)->get('/attendance/detail/' . $attendance->id);

        $response->assertStatus(200);
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    public function test_detail_page_displays_break_time()
    {
        $user = $this->createVerifiedUser();
        $attendance = $this->createAttendanceWithBreak($user);

        $response = $this->actingAs($user)->get('/attendance/detail/' . $attendance->id);

        $response->assertStatus(200);
        $response->assertSee('12:00');
        $response->assertSee('13:00');
    }
}