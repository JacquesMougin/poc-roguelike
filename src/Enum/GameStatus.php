<?php

declare(strict_types=1);

namespace App\Enum;

enum GameStatus: string
{
    case Lobby = 'lobby';
    case InProgress = 'in_progress';
    case Finished = 'finished';
}
