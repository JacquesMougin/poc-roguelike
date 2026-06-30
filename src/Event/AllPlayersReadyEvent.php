<?php

declare(strict_types=1);

namespace App\Event;

use App\Entity\Game;

final readonly class AllPlayersReadyEvent
{
    public function __construct(
        public Game $game,
    ) {}
}
