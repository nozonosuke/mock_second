<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAttendanceListTest extends TestCase
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

    private function createUser($name)
    {
        return User::create([
            'name' => $name,
            'email' => uniqid() . '@example.com',
            'password' => bcrypt('password123'),
            'is_admin' => false,
            'email_verified_at' => Carbon::now(),
        ]);
    }

    private function createAttendance($user, $date, $clockIn, $clockOut, $breakStart, $breakEnd)
    {
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => $date,
            'clock_in' => $date . ' ' . $clockIn . ':00',
            'clock_out' => $date . ' ' . $clockOut . ':00',
            'status' => 'done',
        ]);

        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start' => $date . ' ' . $breakStart . ':00',
            'break_end' => $date . ' ' . $breakEnd . ':00',
        ]);

        return $attendance;
    }

    public function test_admin_can_see_all_users_attendance_for_the_day()
    {
        $admin = $this->createAdminUser();

        $user1 = $this->createUser('山田太郎');
        $user2 = $this->createUser('佐藤花子');

        $today = Carbon::today()->format('Y-m-d');

        $this->createAttendance($user1, $today, '09:00', '18:00', '12:00', '13:00');
        $this->createAttendance($user2, $today, '10:00', '19:00', '14:00', '15:00');

        $response = $this->actingAs($admin)->get('/admin/attendance/list');

        $response->assertStatus(200);

        $response->assertSee('山田太郎');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('1:00');

        $response->assertSee('佐藤花子');
        $response->assertSee('10:00');
        $response->assertSee('19:00');
        $response->assertSee('1:00');
    }

    public function test_current_date_is_displayed_when_admin_attendance_list_is_opened()
    {
        $admin = $this->createAdminUser();

        $today = Carbon::today()->format('Y/m/d');

        $response = $this->actingAs($admin)->get('/admin/attendance/list');

        $response->assertStatus(200);
        $response->assertSee($today);
    }

    public function test_previous_day_button_displays_previous_day_attendance()
    {
        $admin = $this->createAdminUser();
        $user = $this->createUser('前日ユーザー');

        $yesterday = Carbon::today()->subDay()->format('Y-m-d');

        $this->createAttendance($user, $yesterday, '08:00', '17:00', '12:00', '13:00');

        $response = $this->actingAs($admin)->get('/admin/attendance/list?date=' . $yesterday);

        $response->assertStatus(200);

        $response->assertSee(Carbon::parse($yesterday)->format('Y/m/d'));
        $response->assertSee('前日ユーザー');
        $response->assertSee('08:00');
        $response->assertSee('17:00');
    }

    public function test_next_day_button_displays_next_day_attendance()
    {
        $admin = $this->createAdminUser();
        $user = $this->createUser('翌日ユーザー');

        $tomorrow = Carbon::today()->addDay()->format('Y-m-d');

        $this->createAttendance($user, $tomorrow, '11:00', '20:00', '15:00', '16:00');

        $response = $this->actingAs($admin)->get('/admin/attendance/list?date=' . $tomorrow);

        $response->assertStatus(200);

        $response->assertSee(Carbon::parse($tomorrow)->format('Y/m/d'));
        $response->assertSee('翌日ユーザー');
        $response->assertSee('11:00');
        $response->assertSee('20:00');
    }
}