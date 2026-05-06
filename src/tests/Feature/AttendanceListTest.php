<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceListTest extends TestCase
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

    public function test_all_own_attendances_are_displayed()
    {
        $user = $this->createVerifiedUser();

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-04-01',
            'clock_in' => '2026-04-01 09:00:00',
            'clock_out' => '2026-04-01 18:00:00',
            'status' => 'done',
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-04-02',
            'clock_in' => '2026-04-02 10:00:00',
            'clock_out' => '2026-04-02 19:00:00',
            'status' => 'done',
        ]);

        $response = $this->actingAs($user)->get('/attendance/list?month=2026-04');

        $response->assertStatus(200);
        $response->assertSee('04/01(水)');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('04/02(木)');
        $response->assertSee('10:00');
        $response->assertSee('19:00');
    }

    public function test_current_month_is_displayed()
    {
        Carbon::setTestNow(Carbon::parse('2026-04-15 09:00:00'));

        $user = $this->createVerifiedUser();

        $response = $this->actingAs($user)->get('/attendance/list');

        $response->assertStatus(200);
        $response->assertSee('2026/04');

        Carbon::setTestNow();
    }

    public function test_previous_month_attendances_are_displayed()
    {
        $user = $this->createVerifiedUser();

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-03-10',
            'clock_in' => '2026-03-10 09:00:00',
            'clock_out' => '2026-03-10 18:00:00',
            'status' => 'done',
        ]);

        $response = $this->actingAs($user)->get('/attendance/list?month=2026-03');

        $response->assertStatus(200);
        $response->assertSee('2026/03');
        $response->assertSee('03/10(火)');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    public function test_next_month_attendances_are_displayed()
    {
        $user = $this->createVerifiedUser();

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-05-10',
            'clock_in' => '2026-05-10 09:00:00',
            'clock_out' => '2026-05-10 18:00:00',
            'status' => 'done',
        ]);

        $response = $this->actingAs($user)->get('/attendance/list?month=2026-05');

        $response->assertStatus(200);
        $response->assertSee('2026/05');
        $response->assertSee('05/10(日)');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    public function test_detail_link_redirects_to_attendance_detail_page()
    {
        $user = $this->createVerifiedUser();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-04-01',
            'clock_in' => '2026-04-01 09:00:00',
            'clock_out' => '2026-04-01 18:00:00',
            'status' => 'done',
        ]);

        $response = $this->actingAs($user)->get('/attendance/list?month=2026-04');

        $response->assertStatus(200);
        $response->assertSee('詳細');

        $detailResponse = $this->actingAs($user)->get('/attendance/detail/' . $attendance->id);

        $detailResponse->assertStatus(200);
    }
}