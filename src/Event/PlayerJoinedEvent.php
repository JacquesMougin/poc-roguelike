<?php

declare(strict_types=1);

namespace App\Event;

use App\Entity\Game;
use App\Entity\Player;

final readonly class PlayerJoinedEvent
{
    public function __construct(
        public Player $player,
        public Game $game,
    ) {}
}
