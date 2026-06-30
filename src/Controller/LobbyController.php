<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Player;
use App\Enum\GameStatus;
use App\Exception\LobbyFullException;
use App\Repository\GameRepository;
use App\Repository\PlayerRepository;
use App\Service\LobbyService;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class LobbyController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function home(LobbyService $lobby): Response
    {
        $game = $lobby->createGame();

        return $this->redirectToRoute('lobby_desktop', ['code' => $game->getCode()]);
    }

    #[Route('/game/{code}', name: 'lobby_desktop')]
    public function desktop(string $code, GameRepository $games): Response
    {
        $game = $games->findByCode($code) ?? throw $this->createNotFoundException();

        return $this->render('lobby/desktop.html.twig', ['game' => $game]);
    }

    #[Route('/join/{code}', name: 'lobby_join', methods: ['GET'])]
    public function join(string $code, GameRepository $games): Response
    {
        $game = $games->findByCode($code) ?? throw $this->createNotFoundException();

        return $this->render('lobby/mobile.html.twig', ['game' => $game]);
    }

    #[Route('/join/{code}', name: 'lobby_join_post', methods: ['POST'])]
    public function joinPost(string $code, Request $request, GameRepository $games, LobbyService $lobby): Response
    {
        $game = $games->findByCode($code) ?? throw $this->createNotFoundException();
        $name = trim($request->request->getString('name'));

        if ($name === '') {
            return $this->redirectToRoute('lobby_join', ['code' => $code]);
        }

        try {
            $player = $lobby->joinGame($game, $name);
        } catch (LobbyFullException) {
            return $this->redirectToRoute('lobby_join', ['code' => $code]);
        }

        $request->getSession()->set('player_id', $player->getId());

        return $this->redirectToRoute('lobby_waiting', ['code' => $code, 'id' => $player->getId()]);
    }

    #[Route('/game/{code}/waiting/{id}', name: 'lobby_waiting', methods: ['GET'])]
    public function waiting(string $code, int $id, Request $request, GameRepository $games, PlayerRepository $players): Response
    {
        $game = $games->findByCode($code) ?? throw $this->createNotFoundException();
        $player = $players->find($id);

        if (!$player instanceof Player || $player->getGame() !== $game) {
            throw $this->createNotFoundException();
        }

        $this->assertOwnsPlayer($request, $player);

        if ($game->getStatus() === GameStatus::InProgress) {
            return $this->render('lobby/waiting.html.twig', [
                'game' => $game,
                'player' => $player,
                'playUrl' => $this->generateUrl('game_play', ['code' => $code]),
            ]);
        }

        return $this->render('lobby/waiting.html.twig', ['game' => $game, 'player' => $player]);
    }

    #[Route('/game/{code}/qr', name: 'lobby_qr')]
    public function qr(string $code, GameRepository $games): Response
    {
        $game = $games->findByCode($code) ?? throw $this->createNotFoundException();

        $url = $this->generateUrl('lobby_join', ['code' => $game->getCode()], UrlGeneratorInterface::ABSOLUTE_URL);

        $qrCode = new QrCode(
            data: $url,
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: 300,
            margin: 10,
        );

        $result = (new SvgWriter())->write($qrCode);

        return new Response($result->getString(), 200, ['Content-Type' => $result->getMimeType()]);
    }

    #[Route('/game/{code}/ready/{id}', name: 'lobby_ready', methods: ['POST'])]
    public function ready(string $code, int $id, Request $request, GameRepository $games, PlayerRepository $players, LobbyService $lobby): Response
    {
        $game = $games->findByCode($code) ?? throw $this->createNotFoundException();
        $player = $players->find($id);

        if (!$player instanceof Player || $player->getGame() !== $game) {
            throw $this->createNotFoundException();
        }

        $this->assertOwnsPlayer($request, $player);
        $lobby->toggleReady($game, $player);

        return $this->redirectToRoute('lobby_waiting', ['code' => $code, 'id' => $id]);
    }

    #[Route('/game/{code}/leave/{id}', name: 'lobby_leave', methods: ['POST'])]
    public function leave(string $code, int $id, Request $request, GameRepository $games, PlayerRepository $players, LobbyService $lobby): Response
    {
        $game = $games->findByCode($code) ?? throw $this->createNotFoundException();
        $player = $players->find($id);

        if (!$player instanceof Player || $player->getGame() !== $game) {
            throw $this->createNotFoundException();
        }

        $this->assertOwnsPlayer($request, $player);
        $request->getSession()->remove('player_id');
        $lobby->leaveGame($game, $player);

        return $this->redirectToRoute('lobby_join', ['code' => $code]);
    }

    private function assertOwnsPlayer(Request $request, Player $player): void
    {
        if ($request->getSession()->get('player_id') !== $player->getId()) {
            throw new AccessDeniedHttpException();
        }
    }
}
