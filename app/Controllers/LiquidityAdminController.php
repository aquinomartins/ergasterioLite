<?php declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Core\Csrf;
use App\Repositories\LiquidityActionRepository;
use App\Repositories\LiquidityTeamRepository;
use App\Services\LiquidityPoolService;
use App\Services\LiquidityPredictionMarketService;
use DomainException;

final class LiquidityAdminController extends Controller
{
    private LiquidityPoolService $s;
    private LiquidityPredictionMarketService $pred;

    public function __construct()
    {
        $this->s = new LiquidityPoolService();
        $this->pred = new LiquidityPredictionMarketService();
    }

    public function index(): void { $this->view('liquidity.admin.index', ['sessions' => (new \App\Repositories\LiquiditySessionRepository())->getAll()]); }
    public function create(): void { $this->view('liquidity.admin.create'); }
    public function store(): void { try { $se = $this->s->createSession($_POST, null); $this->redirectTo('/liquidity/' . $se['id']); } catch (DomainException $e) { Session::flash('error', $e->getMessage()); $this->redirectTo('/liquidity/create'); } }

    public function show(string $id): void
    {
        $sid = (int) $id;
        $state = $this->s->getProjectorState($sid);
        $session = $state['session'];
        $round = (int) ($session['current_round'] ?? 1);
        $teams = (new LiquidityTeamRepository())->getBySessionId($sid);
        $actionRepo = new LiquidityActionRepository();
        $actedByTeam = [];
        foreach ($teams as $team) { $actedByTeam[(int)$team['id']] = $actionRepo->hasTeamActed($sid, $round, (int)$team['id']); }

        $this->view('liquidity.admin.show', [
            'state' => $state,
            'teams' => $teams,
            'actedByTeam' => $actedByTeam,
            'predictionMarkets' => $this->pred->getMarketsForSession($sid),
        ]);
    }

    public function teams(string $id): void { $this->view('liquidity.admin.teams', ['sessionId' => (int) $id, 'teams' => (new LiquidityTeamRepository())->getBySessionId((int) $id)]); }
    public function createTeam(string $id): void { $this->s->createTeam((int) $id, (string) ($_POST['name'] ?? '')); $this->redirectTo('/liquidity/' . $id . '/teams'); }
    public function advanceRound(string $id): void { if (!Csrf::verifyFromRequest()) { Session::flash('error','CSRF inválido.'); $this->redirectTo('/liquidity/' . $id);} $this->s->advanceRound((int) $id); $this->redirectTo('/liquidity/' . $id); }
    public function evaluateSemifinal(string $id): void { if (!Csrf::verifyFromRequest()) { Session::flash('error','CSRF inválido.'); $this->redirectTo('/liquidity/' . $id);} $this->s->evaluateSemifinal((int) $id); $this->redirectTo('/liquidity/' . $id); }
    public function closeFinal(string $id): void { if (!Csrf::verifyFromRequest()) { Session::flash('error','CSRF inválido.'); $this->redirectTo('/liquidity/' . $id);} $this->s->closeFinal((int) $id); $this->redirectTo('/liquidity/' . $id); }
    public function closeSession(string $id): void { $this->s->closeSession((int) $id); $this->redirectTo('/liquidity/' . $id); }
    public function projector(string $id): void { $this->view('liquidity.admin.projector', ['sessionId' => (int) $id]); }
}
