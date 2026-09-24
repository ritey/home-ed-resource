<?php

namespace App\Enums;

use Illuminate\Support\Collection;

/**
 * Age bands rather than key stages, which don't exist in Scotland.
 */
enum AgeBand: string
{
    case EarlyYears = 'early-years';
    case Primary = 'primary';
    case Secondary = 'secondary';
    case Post16 = '16-plus';

    public function label(): string
    {
        return match ($this) {
            self::EarlyYears => 'Early years (0–5)',
            self::Primary => 'Primary (5–11)',
            self::Secondary => 'Secondary (11–16)',
            self::Post16 => '16 and over',
        };
    }

    public function min(): int
    {
        return match ($this) {
            self::EarlyYears => 0,
            self::Primary => 5,
            self::Secondary => 11,
            self::Post16 => 16,
        };
    }

    public function max(): ?int
    {
        return match ($this) {
            self::EarlyYears => 5,
            self::Primary => 11,
            self::Secondary => 16,
            self::Post16 => null,
        };
    }

    /**
     * "Ages 5–16", "Ages 11+" -- the overall span of a set of bands.
     *
     * @param  Collection<int, AgeBand>|null  $bands
     */
    public static function span(?Collection $bands): ?string
    {
        if ($bands === null || $bands->isEmpty()) {
            return null;
        }

        $min = $bands->min(fn (AgeBand $b) => $b->min());
        $max = $bands->contains(fn (AgeBand $b) => $b->max() === null)
            ? null
            : $bands->max(fn (AgeBand $b) => $b->max());

        return $max === null ? "Ages {$min}+" : "Ages {$min}–{$max}";
    }
}
