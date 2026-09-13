<?php

declare(strict_types=1);

namespace App\Http\Requests\Project;

use Illuminate\Foundation\Http\FormRequest;
use Laravel\Mcp\Request as McpRequest;

final class ProjectAttributePreparer
{
    public static function merge(FormRequest|McpRequest $request): void
    {
        $payload = $request->all();
        $incoming = [];

        foreach (['currency', 'hours'] as $key) {
            if (array_key_exists($key, $payload)) {
                $incoming[$key] = $payload[$key];
            }
        }

        if ($incoming === []) {
            return;
        }

        $request->merge(self::normalizePresent($incoming));
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public static function normalizePresent(array $input): array
    {
        if (array_key_exists('currency', $input)) {
            $input['currency'] = self::currency($input['currency']);
        }

        if (array_key_exists('hours', $input)) {
            $input['hours'] = self::hours($input['hours']);
        }

        return $input;
    }

    public static function currency(mixed $currency): mixed
    {
        if (! is_string($currency)) {
            return $currency;
        }

        $normalized = strtoupper(trim($currency));

        return $normalized === '' ? null : $normalized;
    }

    public static function hours(mixed $hours): mixed
    {
        return $hours === '' ? null : $hours;
    }
}
