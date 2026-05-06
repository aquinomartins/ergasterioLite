<?php declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Repositories\LiquidityActionRepository;
use App\Repositories\LiquidityEventRepository;
use App\Repositories\LiquidityPoolRepository;
use App\Repositories\LiquidityTeamRepository;
use App\Services\LiquidityPoolService;
use App\Services\LiquidityPredictionMarketService;
use DomainException;

final class LiquidityTeamController extends Controller
{
    private LiquidityPoolService $s;
    private LiquidityPredictionMarketService $pred;

    public function __construct()
    {
        $this->s = new LiquidityPoolService();
        $this->pred = new LiquidityPredictionMarketService();
    }

    public function loginForm(): void { $this->view('liquidity.team.login'); }

    public function login(): void
    {
        try {
            $d = $this->s->loginTeam((string) $_POST['access_code'], (string) $_POST['login_code']);
            Session::set('liquidity_session_id', $d['session']['id']);
            Session::set('liquidity_team_id', $d['team']['id']);
            $this->redirectTo('/liquidity/team/dashboard');
        } catch (DomainException $e) {
            Session::flash('error', $e->getMessage());
            $this->redirectTo('/liquidity/team/login');
        }
    }

    public function dashboard(): void
    {
        $sid = (int) Session::get('liquidity_session_id', 0);
        $tid = (int) Session::get('liquidity_team_id', 0);
        $state = $this->s->getProjectorState($sid);
        $session = $state['session'] ?? [];
        $currentRound = (int) ($session['current_round'] ?? 0);

        $teamRepo = new LiquidityTeamRepository();
        $actionRepo = new LiquidityActionRepository();
        $eventRepo = new LiquidityEventRepository();
        $poolRepo = new LiquidityPoolRepository();

        $team = $teamRepo->findById($tid) ?? [];
        $ranking = $this->s->getRanking($sid);
        $pool = $poolRepo->findBySessionId($sid) ?? [];
        $events = $eventRepo->getRecentBySession($sid, 20);
        $hasActed = $actionRepo->hasTeamActed($sid, $currentRound, $tid);
        $partialScore = $this->s->calculatePartialScore($team, $session);

        $this->view('liquidity.team.dashboard', [
            'session' => $session,
            'team' => $team,
            'pool' => $pool,
            'ranking' => $ranking,
            'events' => $events,
            'hasActed' => $hasActed,
            'partialScore' => $partialScore,
            'currentRound' => $currentRound,
            'predictionMarkets' => $this->pred->getMarketsForTeamDashboard($sid),
        ]);
    }

    public function submitAction(): void
    {
        try {
            $this->s->submitTeamAction(
                (int) Session::get('liquidity_session_id', 0),
                (int) Session::get('liquidity_team_id', 0),
                (string) ($_POST['action_type'] ?? ''),
                (float) ($_POST['quantity'] ?? 1)
            );
            Session::flash('success', 'Ação enviada com sucesso.');
        } catch (DomainException $e) {
            Session::flash('error', $e->getMessage());
        }

        $this->redirectTo('/liquidity/team/dashboard');
    }
}
