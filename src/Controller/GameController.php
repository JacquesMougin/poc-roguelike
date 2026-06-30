<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\GameRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class GameController extends AbstractController
{
    #[Route('/game/{code}/play', name: 'game_play')]
    public function play(string $code, GameRepository $games): Response
    {
        $game = $games->findByCode($code) ?? throw $this->createNotFoundException();

        return $this->render('game/play.html.twig', ['game' => $game]);
    }
}
