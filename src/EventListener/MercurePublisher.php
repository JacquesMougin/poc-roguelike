<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Event\AllPlayersReadyEvent;
use App\Event\PlayerJoinedEvent;
use App\Event\PlayerLeftEvent;
use App\Event\PlayerReadyEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

final class MercurePublisher
{
    public function __construct(
        private readonly HubInterface $hub,
        private readonly Environment $twig,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {}

    #[AsEventListener]
    public function onPlayerJoined(PlayerJoinedEvent $event): void
    {
        $this->hub->publish(new Update(
            'game/' . $event->game->getCode() . '/players',
            $this->twig->render('lobby/turbo/_player_card.stream.html.twig', [
                'player' => $event->player,
                'action' => 'append',
            ]),
        ));
    }

    #[AsEventListener]
    public function onPlayerReady(PlayerReadyEvent $event): void
    {
        $this->hub->publish(new Update(
            'game/' . $event->game->getCode() . '/players',
            $this->twig->render('lobby/turbo/_player_card.stream.html.twig', [
                'player' => $event->player,
                'action' => 'replace',
            ]),
        ));
    }

    #[AsEventListener]
    public function onAllPlayersReady(AllPlayersReadyEvent $event): void
    {
        $playUrl = $this->urlGenerator->generate(
            'game_play',
            ['code' => $event->game->getCode()],
        );

        $this->hub->publish(new Update(
            'game/' . $event->game->getCode() . '/phase',
            $this->twig->render('game/turbo/_game_start.stream.html.twig', [
                'game' => $event->game,
                'playUrl' => $playUrl,
            ]),
        ));
    }

    #[AsEventListener]
    public function onPlayerLeft(PlayerLeftEvent $event): void
    {
        $this->hub->publish(new Update(
            'game/' . $event->game->getCode() . '/players',
            $this->twig->render('lobby/turbo/_player_card.stream.html.twig', [
                'player' => $event->player,
                'action' => 'remove',
            ]),
        ));
    }
}
