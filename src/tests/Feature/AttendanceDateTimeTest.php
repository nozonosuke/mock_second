<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceDateTimeTest extends TestCase
{
    use RefreshDatabase;

    public function test_current_date_is_displayed_in_same_format_as_ui()
    {
        Carbon::setTestNow(Carbon::parse('2026-04-01 09:00:00'));

        $user = User::create([
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'is_admin' => false,
        ]);

        $user->forceFill([
            'email_verified_at' => Carbon::now(),
        ])->save();

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);

        $response->assertSee('2026年4月1日');
        $response->assertSee('(水)');
        $response->assertSee('09:00');

        Carbon::setTestNow();
    }
}