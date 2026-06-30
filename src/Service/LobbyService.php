<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Game;
use App\Entity\Player;
use App\Event\AllPlayersReadyEvent;
use App\Event\PlayerJoinedEvent;
use App\Event\PlayerLeftEvent;
use App\Event\PlayerReadyEvent;
use App\Enum\GameStatus;
use App\Exception\LobbyFullException;
use App\Repository\GameRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class LobbyService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly EventDispatcherInterface $dispatcher,
        private readonly GameRepository $games,
    ) {}

    public function createGame(): Game
    {
        $game = new Game($this->generateUniqueCode());
        $this->em->persist($game);
        $this->em->flush();

        return $game;
    }

    public function joinGame(Game $game, string $name): Player
    {
        if ($game->isFull()) {
            throw new LobbyFullException();
        }

        $player = new Player($name);
        $game->addPlayer($player);
        $this->em->flush();

        $this->dispatcher->dispatch(new PlayerJoinedEvent($player, $game));

        return $player;
    }

    public function toggleReady(Game $game, Player $player): void
    {
        $player->setReady(!$player->isReady());
        $this->em->flush();

        $this->dispatcher->dispatch(new PlayerReadyEvent($player, $game));

        if ($this->areAllPlayersReady($game)) {
            $game->setStatus(GameStatus::InProgress);
            $this->em->flush();
            $this->dispatcher->dispatch(new AllPlayersReadyEvent($game));
        }
    }

    private function areAllPlayersReady(Game $game): bool
    {
        $players = $game->getPlayers();

        return !$players->isEmpty()
            && $players->forAll(fn(int $_, Player $p) => $p->isReady());
    }

    public function leaveGame(Game $game, Player $player): void
    {
        $game->removePlayer($player);
        $this->em->remove($player);
        $this->em->flush();

        $this->dispatcher->dispatch(new PlayerLeftEvent($player, $game));
    }

    private function generateUniqueCode(): string
    {
        do {
            $code = strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
        } while ($this->games->findByCode($code) instanceof Game);

        return $code;
    }
}
