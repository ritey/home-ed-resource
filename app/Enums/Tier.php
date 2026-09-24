<?php

namespace App\Enums;

/**
 * The sections of the list, in display order. The copy for each (heading,
 * aside, empty-state text) lives in config/site.php under `tiers`.
 */
enum Tier: string
{
    case Free = 'free';
    case Paid = 'paid';

    public function label(): string
    {
        return config("site.tiers.{$this->value}.label");
    }

    public function aside(): string
    {
        return config("site.tiers.{$this->value}.aside");
    }

    public function emptyText(): string
    {
        return config("site.tiers.{$this->value}.empty");
    }
}
