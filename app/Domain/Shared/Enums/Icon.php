<?php

namespace App\Domain\Shared\Enums;

use App\Domain\Shared\Concerns\HasTranslatedLabel;

/**
 * Icons that can be assigned to teams and categories.
 *
 * Every value must have a matching path in resources/js/Components/ui/Icon.tsx.
 */
enum Icon: string
{
    use HasTranslatedLabel;

    case Laptop = 'laptop';
    case Key = 'key';
    case Puzzle = 'puzzle';
    case Wifi = 'wifi';
    case Mail = 'mail';
    case Help = 'help';
    case Printer = 'printer';
    case Video = 'video';
    case Pulse = 'pulse';
    case Drop = 'drop';
    case Grid = 'grid';
    case Spark = 'spark';
    case Chart = 'chart';
    case Server = 'server';
    case Users = 'users';
    case Shield = 'shield';
    case Clock = 'clock';
    case Search = 'search';
}
