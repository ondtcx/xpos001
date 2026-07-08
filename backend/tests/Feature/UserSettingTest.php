<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserSettingTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function get_returns_default_when_setting_not_set(): void
    {
        $user = User::factory()->create();

        $result = UserSetting::get($user->id, 'nonexistent_key', 'fallback');

        $this->assertSame('fallback', $result);
    }

    #[Test]
    public function set_persists_and_retrieves_value(): void
    {
        $user = User::factory()->create();

        UserSetting::set($user->id, 'fiado_auto_enabled', 'false');

        $this->assertSame('false', UserSetting::get($user->id, 'fiado_auto_enabled', 'true'));
    }

    #[Test]
    public function get_without_default_returns_null_when_not_set(): void
    {
        $user = User::factory()->create();

        $result = UserSetting::get($user->id, 'nonexistent');

        $this->assertNull($result);
    }

    #[Test]
    public function set_updates_existing_key_for_same_user(): void
    {
        $user = User::factory()->create();

        UserSetting::set($user->id, 'theme', 'dark');
        UserSetting::set($user->id, 'theme', 'light');

        $this->assertSame('light', UserSetting::get($user->id, 'theme'));
    }

    #[Test]
    public function settings_are_isolated_per_user(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        UserSetting::set($userA->id, 'lang', 'es');
        UserSetting::set($userB->id, 'lang', 'en');

        $this->assertSame('es', UserSetting::get($userA->id, 'lang'));
        $this->assertSame('en', UserSetting::get($userB->id, 'lang'));
    }
}
