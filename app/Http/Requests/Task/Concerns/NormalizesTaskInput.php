<?php

declare(strict_types=1);

namespace App\Http\Requests\Task\Concerns;

/**
 * Normalizes calendar task input before validation.
 *
 * Accepts time-only values for starts_at/ends_at (combining them with the
 * task date) and maps common Portuguese priority/status labels to the
 * canonical English values stored by the API.
 */
trait NormalizesTaskInput
{
    /**
     * @var array<string, string>
     */
    private array $priorityAliases = [
        'baixa' => 'low',
        'media' => 'medium',
        'média' => 'medium',
        'alta' => 'high',
    ];

    /**
     * @var array<string, string>
     */
    private array $statusAliases = [
        'pendente' => 'pending',
        'em progresso' => 'in_progress',
        'em_progresso' => 'in_progress',
        'em andamento' => 'in_progress',
        'em_andamento' => 'in_progress',
        'andamento' => 'in_progress',
        'concluida' => 'completed',
        'concluída' => 'completed',
        'concluido' => 'completed',
        'concluído' => 'completed',
        'finalizada' => 'completed',
        'cancelada' => 'cancelled',
        'cancelado' => 'cancelled',
    ];

    /**
     * Combine a date with a time-only value so it becomes a full datetime.
     * Full datetime strings (or null) are returned untouched.
     */
    private function combineDateAndTime(?string $date, ?string $value): ?string
    {
        if ($value === null || $date === null) {
            return $value;
        }

        if (preg_match('/^\d{1,2}:\d{2}(:\d{2})?$/', $value) !== 1) {
            return $value;
        }

        if (substr_count($value, ':') === 1) {
            $value .= ':00';
        }

        return $date.' '.$value;
    }

    private function normalizePriority(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return $this->priorityAliases[mb_strtolower($value)] ?? $value;
    }

    private function normalizeStatus(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return $this->statusAliases[mb_strtolower($value)] ?? $value;
    }
}
