<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminStaffTest extends TestCase
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

    private function createUser($name, $email)
    {
        return User::create([
            'name' => $name,
            'email' => $email,
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

    public function test_admin_can_see_all_general_users_name_and_email()
    {
        $admin = $this->createAdminUser();

        $user1 = $this->createUser('山田太郎', 'yamada@example.com');
        $user2 = $this->createUser('佐藤花子', 'sato@example.com');

        $response = $this->actingAs($admin)->get('/admin/staff/list');

        $response->assertStatus(200);

        $response->assertSee('山田太郎');
        $response->assertSee('yamada@example.com');

        $response->assertSee('佐藤花子');
        $response->assertSee('sato@example.com');

        $response->assertDontSee('管理者ユーザー');
    }

    public function test_admin_can_see_selected_user_attendance_list()
    {
        $admin = $this->createAdminUser();
        $user = $this->createUser('山田太郎', 'yamada@example.com');

        $date = Carbon::today()->format('Y-m-d');

        $this->createAttendance($user, $date, '09:00', '18:00', '12:00', '13:00');

        $response = $this->actingAs($admin)->get('/admin/staff/' . $user->id . '/attendance');

        $response->assertStatus(200);

        $response->assertSee('山田太郎');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('1:00');
    }

    public function test_previous_month_button_displays_previous_month_attendance()
    {
        $admin = $this->createAdminUser();
        $user = $this->createUser('前月ユーザー', 'prev@example.com');

        $previousMonthDate = Carbon::today()->subMonth()->startOfMonth()->format('Y-m-d');

        $this->createAttendance($user, $previousMonthDate, '08:00', '17:00', '12:00', '13:00');

        $response = $this->actingAs($admin)->get(
            '/admin/staff/' . $user->id . '/attendance?month=' . Carbon::parse($previousMonthDate)->format('Y-m')
        );

        $response->assertStatus(200);

        $response->assertSee(Carbon::parse($previousMonthDate)->format('Y/m'));
        $response->assertSee('08:00');
        $response->assertSee('17:00');
    }

    public function test_next_month_button_displays_next_month_attendance()
    {
        $admin = $this->createAdminUser();
        $user = $this->createUser('翌月ユーザー', 'next@example.com');

        $nextMonthDate = Carbon::today()->addMonth()->startOfMonth()->format('Y-m-d');

        $this->createAttendance($user, $nextMonthDate, '10:00', '19:00', '13:00', '14:00');

        $response = $this->actingAs($admin)->get(
            '/admin/staff/' . $user->id . '/attendance?month=' . Carbon::parse($nextMonthDate)->format('Y-m')
        );

        $response->assertStatus(200);

        $response->assertSee(Carbon::parse($nextMonthDate)->format('Y/m'));
        $response->assertSee('10:00');
        $response->assertSee('19:00');
    }

    public function test_detail_button_links_to_attendance_detail_page()
    {
        $admin = $this->createAdminUser();
        $user = $this->createUser('詳細ユーザー', 'detail@example.com');

        $date = Carbon::today()->format('Y-m-d');

        $attendance = $this->createAttendance($user, $date, '09:00', '18:00', '12:00', '13:00');

        $response = $this->actingAs($admin)->get('/admin/staff/' . $user->id . '/attendance');

        $response->assertStatus(200);
        $response->assertSee('/admin/attendance/' . $attendance->id);

        $detailResponse = $this->actingAs($admin)->get('/admin/attendance/' . $attendance->id);

        $detailResponse->assertStatus(200);
        $detailResponse->assertSee('詳細ユーザー');
        $detailResponse->assertSee('09:00');
        $detailResponse->assertSee('18:00');
    }
}