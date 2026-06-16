<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A calendar task planned for a given day and time inside a project.
 *
 * @property int $user_id
 * @property int $project_id
 */
#[Fillable([
    'user_id',
    'project_id',
    'title',
    'task_date',
    'starts_at',
    'ends_at',
    'notes',
    'location',
    'priority',
    'status',
    'notify',
    'notify_minutes_before',
])]
class Task extends Model
{
    /** @use HasFactory<TaskFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'task_date' => 'date',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'notify' => 'boolean',
            'notify_minutes_before' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
