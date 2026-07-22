<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Passport\Passport;
use Tests\TestCase;

class TelegramSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_their_telegram_settings(): void
    {
        $user = User::factory()->create([
            'telegram_chat_id' => '987654321',
            'telegram_notifications_enabled' => true,
        ]);

        Passport::actingAs($user);

        $this->getJson('/api/me/telegram')
            ->assertOk()
            ->assertJsonPath('data.telegram_chat_id', '987654321')
            ->assertJsonPath('data.telegram_notifications_enabled', true);
    }

    public function test_user_can_save_telegram_chat_id(): void
    {
        $user = User::factory()->create();

        Passport::actingAs($user);

        $this->putJson('/api/me/telegram', [
            'telegram_chat_id' => '123456789',
            'telegram_notifications_enabled' => true,
        ])
            ->assertOk()
            ->assertJsonPath('data.telegram_chat_id', '123456789')
            ->assertJsonPath('data.telegram_notifications_enabled', true);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'telegram_chat_id' => '123456789',
            'telegram_notifications_enabled' => true,
        ]);
    }

    public function test_user_can_enable_and_disable_telegram_notifications(): void
    {
        $user = User::factory()->create([
            'telegram_chat_id' => '123456789',
            'telegram_notifications_enabled' => true,
        ]);

        Passport::actingAs($user);

        $this->putJson('/api/me/telegram', [
            'telegram_chat_id' => '123456789',
            'telegram_notifications_enabled' => false,
        ])
            ->assertOk()
            ->assertJsonPath('data.telegram_notifications_enabled', false);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'telegram_notifications_enabled' => false,
        ]);
    }

    public function test_telegram_notifications_enabled_is_required_and_boolean(): void
    {
        Passport::actingAs(User::factory()->create());

        $this->putJson('/api/me/telegram', [
            'telegram_chat_id' => '123456789',
            'telegram_notifications_enabled' => 'maybe',
        ])->assertStatus(422)->assertJsonValidationErrors('telegram_notifications_enabled');
    }

    public function test_telegram_settings_require_authentication(): void
    {
        $this->getJson('/api/me/telegram')->assertUnauthorized();
        $this->putJson('/api/me/telegram', [
            'telegram_notifications_enabled' => true,
        ])->assertUnauthorized();
    }

    public function test_user_can_send_a_test_message(): void
    {
        config(['services.telegram.bot_token' => 'test-token']);
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true], 200)]);

        $user = User::factory()->create([
            'telegram_chat_id' => '123456789',
            'telegram_notifications_enabled' => true,
        ]);

        Passport::actingAs($user);

        $this->postJson('/api/me/telegram/test')
            ->assertOk()
            ->assertJsonPath('message', 'Mensagem de teste enviada com sucesso.');

        Http::assertSent(fn ($request) => $request['chat_id'] === '123456789');
    }

    public function test_test_message_requires_a_chat_id(): void
    {
        Http::fake();

        Passport::actingAs(User::factory()->create(['telegram_chat_id' => null]));

        $this->postJson('/api/me/telegram/test')->assertStatus(422);

        Http::assertNothingSent();
    }

    public function test_test_message_reports_delivery_failure(): void
    {
        config(['services.telegram.bot_token' => 'test-token']);
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => false], 500)]);

        Passport::actingAs(User::factory()->create([
            'telegram_chat_id' => '123456789',
            'telegram_notifications_enabled' => true,
        ]));

        $this->postJson('/api/me/telegram/test')->assertStatus(502);
    }
}
