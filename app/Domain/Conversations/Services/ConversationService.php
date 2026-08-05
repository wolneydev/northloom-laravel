<?php

declare(strict_types=1);

namespace App\Domain\Conversations\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Laravel\Ai\Models\Conversation;
use Laravel\Ai\Models\ConversationMessage;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Reads the authenticated user's AI conversations (Laravel AI package
 * tables) alongside which agent produced each conversation's latest reply.
 */
final readonly class ConversationService
{
    public function __construct()
    {
        Conversation::resolveRelationUsing(
            'latestMessage',
            fn (Conversation $conversation) => $conversation->hasOne(ConversationMessage::class, 'conversation_id')->latestOfMany('created_at'),
        );
    }

    /**
     * @return LengthAwarePaginator<int, Conversation>
     */
    public function listForUser(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return Conversation::query()
            ->where('participant_type', Conversation::participantType($user))
            ->where('participant_id', Conversation::participantKey($user))
            ->with('latestMessage')
            ->latest('updated_at')
            ->paginate($perPage);
    }

    /**
     * Load one conversation owned by the user, including ordered messages.
     */
    public function getForUser(User $user, string $conversationId): Conversation
    {
        $conversation = Conversation::query()
            ->with(['messages' => fn ($query) => $query->orderBy('created_at')->orderBy('id')])
            ->find($conversationId);

        if ($conversation === null) {
            throw new NotFoundHttpException('Conversation not found.');
        }

        $sameType = $conversation->participant_type === Conversation::participantType($user);
        $sameKey = (string) $conversation->participant_id === (string) Conversation::participantKey($user);

        if (! $sameType || ! $sameKey) {
            throw new AccessDeniedHttpException('This conversation does not belong to you.');
        }

        return $conversation;
    }
}
