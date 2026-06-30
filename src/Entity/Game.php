<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\GameStatus;
use App\Repository\GameRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GameRepository::class)]
class Game
{
    public const int MAX_PLAYERS = 4;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 8, unique: true)]
    private string $code;

    #[ORM\Column(length: 20, enumType: GameStatus::class)]
    private GameStatus $status = GameStatus::Lobby;

    #[ORM\OneToMany(targetEntity: Player::class, mappedBy: 'game', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $players;

    public function __construct(string $code)
    {
        $this->code = $code;
        $this->players = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getCode(): string { return $this->code; }

    public function getStatus(): GameStatus { return $this->status; }

    public function setStatus(GameStatus $status): static
    {
        $this->status = $status;
        return $this;
    }

    /** @return Collection<int, Player> */
    public function getPlayers(): Collection { return $this->players; }

    public function addPlayer(Player $player): static
    {
        if (!$this->players->contains($player)) {
            $this->players->add($player);
            $player->setGame($this);
        }
        return $this;
    }

    public function removePlayer(Player $player): static
    {
        $this->players->removeElement($player);
        return $this;
    }

    public function isFull(): bool
    {
        return $this->players->count() >= self::MAX_PLAYERS;
    }
}
