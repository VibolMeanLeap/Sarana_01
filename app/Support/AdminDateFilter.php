<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Shared date-range behavior for administrator list pages.
 * Presets intentionally end on today's local date so future-dated rows are not
 * included in Today / This week / This month.
 */
class AdminDateFilter
{
    public const PRESETS = ['today', 'week', 'month', 'all', 'custom'];

    /**
     * @param array{from?:array<int,string>,to?:array<int,string>,preset?:array<int,string>} $legacy
     * @return array{preset:string,from:?string,to:?string}
     */
    public static function resolve(
        Request $request,
        string $fromField = 'start_date',
        string $toField = 'end_date',
        array $legacy = []
    ): array {
        $fromFields = array_values(array_unique(array_merge([$fromField], $legacy['from'] ?? [])));
        $toFields = array_values(array_unique(array_merge([$toField], $legacy['to'] ?? [])));
        $rules = [
            'date_preset' => ['nullable', Rule::in(self::PRESETS)],
        ];

        foreach ($fromFields as $field) {
            $rules[$field] = ['nullable', 'date_format:Y-m-d'];
        }
        foreach ($toFields as $field) {
            $rules[$field] = ['nullable', 'date_format:Y-m-d'];
        }
        foreach (($legacy['preset'] ?? []) as $field) {
            $rules[$field] = ['nullable', Rule::in(['today', 'week', 'month', 'all', '7days', '30days', 'this_month'])];
        }

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $from = self::firstFilled($request, $fromFields);
        $to = self::firstFilled($request, $toFields);

        if ($from && $to && $to < $from) {
            throw ValidationException::withMessages([
                $toField => 'The end date must be on or after the start date.',
            ]);
        }

        $preset = $request->input('date_preset');
        if (! $preset) {
            foreach (($legacy['preset'] ?? []) as $field) {
                $legacyValue = $request->input($field);
                if ($legacyValue) {
                    $preset = match ($legacyValue) {
                        '7days' => 'week',
                        '30days', 'this_month' => 'month',
                        default => $legacyValue,
                    };
                    break;
                }
            }
        }
        if (! $preset) {
            $preset = ($from || $to) ? 'custom' : 'all';
        }

        $today = today();
        [$resolvedFrom, $resolvedTo] = match ($preset) {
            'today' => [$today->toDateString(), $today->toDateString()],
            'week' => [$today->copy()->startOfWeek(Carbon::MONDAY)->toDateString(), $today->toDateString()],
            'month' => [$today->copy()->startOfMonth()->toDateString(), $today->toDateString()],
            'all' => [null, null],
            default => [$from, $to],
        };

        return [
            'preset' => $preset,
            'from' => $resolvedFrom,
            'to' => $resolvedTo,
        ];
    }

    /** Apply the resolved range to an Eloquent or query builder. */
    public static function apply(EloquentBuilder|QueryBuilder $query, string $column, array $range): EloquentBuilder|QueryBuilder
    {
        if (! empty($range['from'])) {
            $query->whereDate($column, '>=', $range['from']);
        }
        if (! empty($range['to'])) {
            $query->whereDate($column, '<=', $range['to']);
        }

        return $query;
    }

    private static function firstFilled(Request $request, array $fields): ?string
    {
        foreach ($fields as $field) {
            if ($request->filled($field)) {
                return (string) $request->input($field);
            }
        }

        return null;
    }
}
