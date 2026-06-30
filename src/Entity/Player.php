<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\PlayerRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PlayerRepository::class)]
class Player
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 30)]
    private string $name;

    #[ORM\Column]
    private bool $ready = false;

    #[ORM\ManyToOne(inversedBy: 'players')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Game $game = null;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function getId(): ?int { return $this->id; }

    public function getName(): string { return $this->name; }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function isReady(): bool { return $this->ready; }

    public function setReady(bool $ready): static
    {
        $this->ready = $ready;
        return $this;
    }

    public function getGame(): ?Game { return $this->game; }

    public function setGame(?Game $game): static
    {
        $this->game = $game;
        return $this;
    }
}
