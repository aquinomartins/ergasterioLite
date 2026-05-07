<?php declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Csrf;
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
        if ($sid <= 0 || $tid <= 0) {
            Session::flash('error', 'Faça login da equipe para acessar a arena.');
            $this->redirectTo('/liquidity/team/login');
        }

        $session = $this->s->getProjectorState($sid)['session'] ?? [];
        $currentRound = (int) ($session['current_round'] ?? 1);

        $teamRepo = new LiquidityTeamRepository();
        $actionRepo = new LiquidityActionRepository();
        $eventRepo = new LiquidityEventRepository();
        $poolRepo = new LiquidityPoolRepository();

        $team = $teamRepo->findById($tid) ?? [];
        $pool = $poolRepo->findBySessionId($sid) ?? [];
        $hasActed = $actionRepo->hasTeamActed($sid, $currentRound, $tid);
        $lastAction = $actionRepo->getLastActionForTeam($sid, $currentRound, $tid);

        $this->view('liquidity.team.dashboard', [
            'session' => $session,
            'team' => $team,
            'pool' => $pool,
            'currentRound' => $currentRound,
            'hasActed' => $hasActed,
            'lastAction' => $lastAction,
            'events' => $eventRepo->getRecentBySession($sid, 30),
            'ranking' => $this->s->getRanking($sid),
            'partialScore' => $this->s->calculatePartialScore($team, $session),
            'predictionMarkets' => $this->pred->getMarketsForTeamDashboard($sid),
        ]);
    }

    public function submitAction(): void
    {
        $sid = (int) Session::get('liquidity_session_id', 0);
        $tid = (int) Session::get('liquidity_team_id', 0);
        if ($sid <= 0 || $tid <= 0) {
            Session::flash('error', 'Sessão da equipe inválida.');
            $this->redirectTo('/liquidity/team/login');
        }

        if (!Csrf::verifyFromRequest()) {
            Session::flash('error', 'Token CSRF inválido. Recarregue a página e tente novamente.');
            $this->redirectTo('/liquidity/team/dashboard');
        }

        try {
            $this->s->submitTeamAction(
                $sid,
                $tid,
                (string) ($_POST['action_type'] ?? ''),
                (float) ($_POST['quantity'] ?? 1),
                [
                    'target_team_id' => isset($_POST['target_team_id']) ? (int) $_POST['target_team_id'] : null,
                    'price' => isset($_POST['price']) ? (float) $_POST['price'] : null,
                ]
            );
            $messages = [
                'deposit_nft' => 'Você entrou na piscina: -1 NFT, +10 BTC, +1 cota.',
                'withdraw_nft_btc' => 'Você retirou uma NFT usando BTC.',
                'withdraw_nft_cash' => 'Você retirou uma NFT usando dinheiro.',
                'buy_btc' => 'Você comprou BTC.',
                'sell_btc' => 'Você vendeu BTC.',
                'sell_nft' => 'Você vendeu uma NFT em mãos.',
                'sell_share' => 'Você vendeu uma cota da piscina.',
                'trade_nft_between_teams' => 'Você comprou 1 NFT de outra equipe.',
                'pass' => 'Você passou a vez.',
            ];
            $actionType = (string) ($_POST['action_type'] ?? '');
            Session::flash('success', $messages[$actionType] ?? 'Ação enviada com sucesso.');
        } catch (DomainException $e) {
            Session::flash('error', $e->getMessage());
        } catch (\Throwable $e) {
            Session::flash('error', 'Não foi possível processar sua ação agora.');
        }

        $this->redirectTo('/liquidity/team/dashboard');
    }
}
