<?php

namespace App\Enums;

/**
 * Cost. A filter on the list and a label on each entry. The copy for each
 * lives in config/site.php under `tiers`.
 */
enum Tier: string
{
    case Free = 'free';
    case Paid = 'paid';

    public function label(): string
    {
        return config("site.tiers.{$this->value}.label");
    }

    public function emptyText(): string
    {
        return config("site.tiers.{$this->value}.empty");
    }
}
