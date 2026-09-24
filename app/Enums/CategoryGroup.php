<?php

namespace App\Enums;

enum CategoryGroup: string
{
    case Subject = 'subject';
    case Practical = 'practical';

    public function heading(): string
    {
        return match ($this) {
            self::Subject => 'By subject',
            self::Practical => 'Getting on with home ed',
        };
    }
}
