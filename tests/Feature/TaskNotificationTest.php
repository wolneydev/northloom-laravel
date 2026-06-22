<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TaskNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-06-16 10:00:00');
        config(['services.telegram.bot_token' => 'test-token']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    /**
     * Build a task whose notification time has already arrived for a user with
     * Telegram enabled, overriding any attribute as needed.
     *
     * @param  array<string, mixed>  $taskAttributes
     * @param  array<string, mixed>  $userAttributes
     */
    private function createDueTask(array $taskAttributes = [], array $userAttributes = []): Task
    {
        $user = User::factory()->create(array_merge([
            'telegram_chat_id' => '123456789',
            'telegram_notifications_enabled' => true,
        ], $userAttributes));

        $project = Project::factory()->create(['user_id' => $user->id]);

        return Task::factory()->forProject($project)->create(array_merge([
            'title' => 'Reunião de planejamento',
            'task_date' => '2026-06-16',
            'starts_at' => '2026-06-16 10:10:00',
            'location' => 'Sala 1',
            'notes' => 'Levar relatório',
            'notify' => true,
            'notify_at_datetime' => '2026-06-16 09:40:00',
            'notification_sent_at' => null,
        ], $taskAttributes));
    }

    public function test_command_sends_notification_when_task_is_due(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $task = $this->createDueTask();

        $this->artisan('tasks:send-notifications')->assertSuccessful();

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/bottest-token/sendMessage')
                && $request['chat_id'] === '123456789'
                && str_contains($request['text'], 'Reunião de planejamento')
                && str_contains($request['text'], 'Lembrete de tarefa');
        });

        $this->assertNotNull($task->fresh()->notification_sent_at);
    }

    public function test_command_marks_notification_sent_at_after_successful_send(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true], 200),
        ]);

        $task = $this->createDueTask();

        $this->artisan('tasks:send-notifications')->assertSuccessful();

        $this->assertEquals(
            Carbon::now()->toDateTimeString(),
            $task->fresh()->notification_sent_at->toDateTimeString(),
        );
    }

    public function test_command_does_not_send_when_notify_is_false(): void
    {
        Http::fake();

        $task = $this->createDueTask(['notify' => false]);

        $this->artisan('tasks:send-notifications')->assertSuccessful();

        Http::assertNothingSent();
        $this->assertNull($task->fresh()->notification_sent_at);
    }

    public function test_command_does_not_send_when_notification_already_sent(): void
    {
        Http::fake();

        $task = $this->createDueTask(['notification_sent_at' => '2026-06-16 09:50:00']);

        $this->artisan('tasks:send-notifications')->assertSuccessful();

        Http::assertNothingSent();
        $this->assertEquals(
            '2026-06-16 09:50:00',
            $task->fresh()->notification_sent_at->toDateTimeString(),
        );
    }

    public function test_command_does_not_send_when_user_has_no_chat_id(): void
    {
        Http::fake();

        $task = $this->createDueTask(userAttributes: ['telegram_chat_id' => null]);

        $this->artisan('tasks:send-notifications')->assertSuccessful();

        Http::assertNothingSent();
        $this->assertNull($task->fresh()->notification_sent_at);
    }

    public function test_command_does_not_send_when_notifications_disabled(): void
    {
        Http::fake();

        $task = $this->createDueTask(userAttributes: ['telegram_notifications_enabled' => false]);

        $this->artisan('tasks:send-notifications')->assertSuccessful();

        Http::assertNothingSent();
        $this->assertNull($task->fresh()->notification_sent_at);
    }

    public function test_command_does_not_send_before_the_notification_window(): void
    {
        Http::fake();

        // Notification is scheduled for 11:30; current time is 10:00.
        $task = $this->createDueTask([
            'starts_at' => '2026-06-16 12:00:00',
            'notify_at_datetime' => '2026-06-16 11:30:00',
        ]);

        $this->artisan('tasks:send-notifications')->assertSuccessful();

        Http::assertNothingSent();
        $this->assertNull($task->fresh()->notification_sent_at);
    }

    public function test_command_does_not_mark_notification_when_send_fails(): void
    {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => false], 500),
        ]);

        $task = $this->createDueTask();

        $this->artisan('tasks:send-notifications')->assertSuccessful();

        Http::assertSent(fn ($request) => str_contains($request->url(), 'sendMessage'));
        $this->assertNull($task->fresh()->notification_sent_at);
    }
}
