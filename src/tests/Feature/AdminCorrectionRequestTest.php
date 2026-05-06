<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\CorrectionRequest;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCorrectionRequestTest extends TestCase
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

    private function createCorrectionRequest($user, $attendance, $status = 'pending', $note = '修正申請理由です')
    {
        return CorrectionRequest::create([
            'user_id' => $user->id,
            'attendance_id' => $attendance->id,
            'requested_clock_in' => '10:00',
            'requested_clock_out' => '19:00',
            'requested_breaks' => [
                [
                    'break_start' => '13:00',
                    'break_end' => '14:00',
                ],
            ],
            'note' => $note,
            'status' => $status,
        ]);
    }

    public function test_admin_can_see_all_pending_correction_requests()
    {
        $admin = $this->createAdminUser();

        $user1 = $this->createUser('山田太郎');
        $attendance1 = $this->createAttendanceWithBreak($user1);
        $this->createCorrectionRequest($user1, $attendance1, 'pending', '山田の未承認申請');

        $user2 = $this->createUser('佐藤花子');
        $attendance2 = $this->createAttendanceWithBreak($user2);
        $this->createCorrectionRequest($user2, $attendance2, 'pending', '佐藤の未承認申請');

        $response = $this->actingAs($admin)->get('/admin/request/list?status=pending');

        $response->assertStatus(200);
        $response->assertSee('山田太郎');
        $response->assertSee('山田の未承認申請');
        $response->assertSee('佐藤花子');
        $response->assertSee('佐藤の未承認申請');
    }

    public function test_admin_can_see_all_approved_correction_requests()
    {
        $admin = $this->createAdminUser();

        $user1 = $this->createUser('承認済みユーザー1');
        $attendance1 = $this->createAttendanceWithBreak($user1);
        $this->createCorrectionRequest($user1, $attendance1, 'approved', '承認済み申請1');

        $user2 = $this->createUser('承認済みユーザー2');
        $attendance2 = $this->createAttendanceWithBreak($user2);
        $this->createCorrectionRequest($user2, $attendance2, 'approved', '承認済み申請2');

        $response = $this->actingAs($admin)->get('/admin/request/list?status=approved');

        $response->assertStatus(200);
        $response->assertSee('承認済みユーザー1');
        $response->assertSee('承認済み申請1');
        $response->assertSee('承認済みユーザー2');
        $response->assertSee('承認済み申請2');
    }

    public function test_admin_can_see_correction_request_detail()
    {
        $admin = $this->createAdminUser();

        $user = $this->createUser('詳細確認ユーザー');
        $attendance = $this->createAttendanceWithBreak($user);
        $correctionRequest = $this->createCorrectionRequest($user, $attendance, 'pending', '詳細画面確認用の備考です');

        $response = $this->actingAs($admin)->get('/admin/request/' . $correctionRequest->id);

        $response->assertStatus(200);
        $response->assertSee('詳細確認ユーザー');
        $response->assertSee('2026');
        $response->assertSee('4月1日');
        $response->assertSee('10:00');
        $response->assertSee('19:00');
        $response->assertSee('13:00');
        $response->assertSee('14:00');
        $response->assertSee('詳細画面確認用の備考です');
    }

    public function test_admin_can_approve_correction_request_and_attendance_is_updated()
    {
        $admin = $this->createAdminUser();

        $user = $this->createUser('承認対象ユーザー');
        $attendance = $this->createAttendanceWithBreak($user);
        $correctionRequest = $this->createCorrectionRequest($user, $attendance, 'pending', '承認処理テストです');

        $response = $this->actingAs($admin)->post('/admin/request/' . $correctionRequest->id . '/approve');

        $response->assertRedirect();

        $this->assertDatabaseHas('correction_requests', [
            'id' => $correctionRequest->id,
            'status' => 'approved',
        ]);

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'clock_in' => '2026-04-01 10:00:00',
            'clock_out' => '2026-04-01 19:00:00',
        ]);

        $this->assertDatabaseHas('break_times', [
            'attendance_id' => $attendance->id,
            'break_start' => '2026-04-01 13:00:00',
            'break_end' => '2026-04-01 14:00:00',
        ]);
    }
}