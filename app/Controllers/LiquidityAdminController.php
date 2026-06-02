<?php declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Core\Csrf;
use App\Repositories\LiquidityActionRepository;
use App\Repositories\LiquidityEventRepository;
use App\Repositories\LiquidityPoolRepository;
use App\Repositories\LiquidityRoundRepository;
use App\Repositories\LiquiditySessionRepository;
use App\Repositories\LiquidityTeamRepository;
use App\Services\LiquidityPoolService;
use App\Services\LiquidityPredictionMarketService;
use DomainException;

final class LiquidityAdminController extends Controller
{
    private ?LiquidityPoolService $s = null;
    private ?LiquidityPredictionMarketService $pred = null;

    private function liquidityService(): LiquidityPoolService
    {
        return $this->s ??= new LiquidityPoolService();
    }

    private function predictionService(): LiquidityPredictionMarketService
    {
        return $this->pred ??= new LiquidityPredictionMarketService();
    }

    public function index(): void { $this->view('liquidity.admin.index', ['sessions' => (new \App\Repositories\LiquiditySessionRepository())->getAll()]); }
    public function create(): void { $this->view('liquidity.admin.create'); }
    public function store(): void { try { $se = $this->liquidityService()->createSession($_POST, null); $this->redirectTo('/liquidity/' . $se['id']); } catch (DomainException $e) { Session::flash('error', $e->getMessage()); $this->redirectTo('/liquidity/create'); } }

    public function show(string $id): void
    {
        $sid = (int) $id;
        $state = $this->liquidityService()->getProjectorState($sid);
        $session = $state['session'];
        $round = (int) ($session['current_round'] ?? 1);
        $teams = (new LiquidityTeamRepository())->getBySessionId($sid);
        $currentRoundState = (new LiquidityRoundRepository())->getCurrentRound($sid, $round);
        $actionRepo = new LiquidityActionRepository();
        $actedByTeam = [];
        $lastActionByTeam = [];
        foreach ($teams as $team) {
            $teamId = (int)$team['id'];
            $actedByTeam[$teamId] = $actionRepo->hasTeamActed($sid, $round, $teamId);
            $lastActionByTeam[$teamId] = $actionRepo->getLastActionForTeam($sid, $round, $teamId);
        }

        $this->view('liquidity.admin.show', [
            'state' => $state,
            'teams' => $teams,
            'actedByTeam' => $actedByTeam,
            'lastActionByTeam' => $lastActionByTeam,
            'currentRoundState' => $currentRoundState,
            'predictionMarkets' => $this->predictionService()->getMarketsForSession($sid),
        ]);
    }

    public function teams(string $id): void { $this->view('liquidity.admin.teams', ['sessionId' => (int) $id, 'teams' => (new LiquidityTeamRepository())->getBySessionId((int) $id)]); }
    public function createTeam(string $id): void { $this->liquidityService()->createTeam((int) $id, (string) ($_POST['name'] ?? '')); $this->redirectTo('/liquidity/' . $id . '/teams'); }

    public function registerTeamAction(string $id): void
    {
        if (!Csrf::verifyFromRequest()) {
            Session::flash('error', 'CSRF inválido.');
            $this->redirectTo('/liquidity/' . $id);
        }

        $actionType = (string) ($_POST['action_type'] ?? '');
        if ($actionType === 'withdraw_nft') {
            $paymentMethod = (string) ($_POST['payment_method'] ?? '');
            $actionType = $paymentMethod === 'cash' ? 'withdraw_nft_cash' : ($paymentMethod === 'btc' ? 'withdraw_nft_btc' : '');
        }

        try {
            $this->liquidityService()->submitTeamAction(
                (int) $id,
                (int) ($_POST['team_id'] ?? 0),
                $actionType,
                isset($_POST['quantity']) && $_POST['quantity'] !== '' ? (float) $_POST['quantity'] : null,
                [
                    'target_team_id' => isset($_POST['target_team_id']) && $_POST['target_team_id'] !== '' ? (int) $_POST['target_team_id'] : null,
                    'price' => isset($_POST['price']) && $_POST['price'] !== '' ? (float) $_POST['price'] : null,
                ]
            );
            Session::flash('success', 'Ação do time registrada com sucesso.');
        } catch (DomainException $e) {
            Session::flash('error', $e->getMessage());
        } catch (\Throwable $e) {
            Session::flash('error', 'Não foi possível registrar a ação do time agora.');
        }

        $this->redirectTo('/liquidity/' . $id);
    }

    public function advanceRound(string $id): void
    {
        if (!Csrf::verifyFromRequest()) {
            Session::flash('error', 'CSRF inválido.');
            $this->redirectTo('/liquidity/' . $id);
        }

        try {
            $this->liquidityService()->advanceRound((int) $id);
            Session::flash('success', 'Rodada encerrada com sucesso.');
        } catch (DomainException $e) {
            Session::flash('error', $e->getMessage());
        } catch (\Throwable $e) {
            Session::flash('error', 'Não foi possível encerrar a rodada agora.');
        }

        $this->redirectTo('/liquidity/' . $id);
    }
    public function evaluateSemifinal(string $id): void
    {
        if (!Csrf::verifyFromRequest()) {
            Session::flash('error', 'CSRF inválido.');
            $this->redirectTo('/liquidity/' . $id);
        }

        try {
            $result = $this->liquidityService()->evaluateSemifinal((int) $id);
            Session::flash(
                'success',
                !empty($result['reevaluated'])
                    ? 'Semifinal reavaliada com sucesso.'
                    : 'Semifinal avaliada com sucesso.'
            );
        } catch (DomainException $e) {
            Session::flash('error', $e->getMessage());
        } catch (\Throwable $e) {
            Session::flash('error', 'Não foi possível avaliar a semifinal agora.');
        }

        $this->redirectTo('/liquidity/' . $id);
    }
    public function closeFinal(string $id): void
    {
        if (!Csrf::verifyFromRequest()) {
            Session::flash('error', 'CSRF inválido.');
            $this->redirectTo('/liquidity/' . $id);
        }

        try {
            $this->liquidityService()->closeFinal((int) $id);
            Session::flash('success', 'Final encerrada com sucesso.');
        } catch (DomainException $e) {
            Session::flash('error', $e->getMessage());
        } catch (\Throwable $e) {
            Session::flash('error', 'Não foi possível encerrar a final agora.');
        }

        $this->redirectTo('/liquidity/' . $id);
    }
    public function closeSession(string $id): void
    {
        try {
            $this->liquidityService()->closeSession((int) $id);
            Session::flash('success', 'Sessão encerrada com sucesso.');
        } catch (DomainException $e) {
            Session::flash('error', $e->getMessage());
        } catch (\Throwable $e) {
            Session::flash('error', 'Não foi possível encerrar a sessão agora.');
        }

        $this->redirectTo('/liquidity/' . $id);
    }
    public function projector(string $id): void
    {
        $sid = (int) $id;

        try {
            $sessionRepo = new LiquiditySessionRepository();
            $session = $sessionRepo->findById($sid);

            if (!$session) {
                http_response_code(404);
                $this->view('liquidity.admin.projector', [
                    'pageTitle' => 'Projetor — Piscina de Liquidez',
                    'projectorError' => 'Piscina não encontrada.',
                ], 'layouts.projector');
                return;
            }

            $round = max(1, (int) ($session['current_round'] ?? 1));
            $teams = (new LiquidityTeamRepository())->getBySessionId($sid) ?: [];
            $pool = (new LiquidityPoolRepository())->findBySessionId($sid) ?: [];
            $feed = (new LiquidityEventRepository())->getRecentBySession($sid, 20) ?: [];
            $currentRoundState = (new LiquidityRoundRepository())->getCurrentRound($sid, $round);
            $actedByTeam = [];
            $lastActionByTeam = [];
            $actionRepo = new LiquidityActionRepository();

            foreach ($teams as $team) {
                $teamId = (int) ($team['id'] ?? 0);
                if ($teamId <= 0) {
                    continue;
                }

                $actedByTeam[$teamId] = $actionRepo->hasTeamActed($sid, $round, $teamId);
                $lastActionByTeam[$teamId] = $actionRepo->getLastActionForTeam($sid, $round, $teamId);
            }

            $state = [
                'session' => $session,
                'pool' => $pool,
                'ranking' => $this->buildProjectorRanking($teams, $session),
                'final_ranking' => $this->buildProjectorFinalRanking($teams, $session),
                'feed' => $feed,
            ];

            $this->view('liquidity.admin.projector', [
                'pageTitle' => 'Projetor — Piscina de Liquidez',
                'state' => $state,
                'teams' => $teams,
                'actedByTeam' => $actedByTeam,
                'lastActionByTeam' => $lastActionByTeam,
                'currentRoundState' => $currentRoundState,
            ], 'layouts.projector');
        } catch (\Throwable $e) {
            error_log(sprintf(
                'Erro ao carregar projetor da piscina %d em %s:%d: %s',
                $sid,
                $e->getFile(),
                $e->getLine(),
                $e->getMessage()
            ));
            http_response_code(500);
            $this->view('liquidity.admin.projector', [
                'pageTitle' => 'Projetor — Piscina de Liquidez',
                'projectorError' => 'Não foi possível carregar os dados da piscina agora.',
            ], 'layouts.projector');
        }
    }

    private function buildProjectorRanking(array $teams, array $session): array
    {
        foreach ($teams as &$team) {
            $team['estimated_wealth'] = (float) ($team['cash_balance'] ?? 0)
                + ((float) ($team['btc_balance'] ?? 0) * (float) ($session['btc_sell_price'] ?? 100))
                + ((int) ($team['nft_balance'] ?? 0) * (float) ($session['nft_sell_price'] ?? 1800))
                + ((int) ($team['pool_shares'] ?? 0) * (float) ($session['share_sell_price'] ?? 500));
            $team['score'] = $team['estimated_wealth'];
            $team['final_cash_score'] = (float) ($team['cash_balance'] ?? 0);
            $team['display_status'] = $this->projectorStatusForTeam($team);
        }
        unset($team);

        usort($teams, static fn(array $a, array $b): int =>
            ((float) ($b['estimated_wealth'] ?? 0) <=> (float) ($a['estimated_wealth'] ?? 0))
            ?: ((float) ($b['cash_balance'] ?? 0) <=> (float) ($a['cash_balance'] ?? 0))
            ?: ((string) ($a['name'] ?? '') <=> (string) ($b['name'] ?? ''))
        );

        foreach ($teams as $index => &$team) {
            $team['general_position'] = $index + 1;
        }
        unset($team);

        return $teams;
    }

    private function buildProjectorFinalRanking(array $teams, array $session): array
    {
        $phase = (string) ($session['session_phase'] ?? 'regular');
        $finalClosed = in_array($phase, ['final_closed', 'closed'], true) || (string) ($session['status'] ?? '') === 'closed';
        $finalists = array_values(array_filter($teams, static fn(array $team): bool =>
            !empty($team['qualified_for_final']) && empty($team['is_eliminated'])
        ));

        usort($finalists, static fn(array $a, array $b): int =>
            ((float) ($b['cash_balance'] ?? 0) <=> (float) ($a['cash_balance'] ?? 0))
            ?: ((string) ($a['name'] ?? '') <=> (string) ($b['name'] ?? ''))
        );

        $topCash = $finalists ? (float) ($finalists[0]['cash_balance'] ?? 0) : null;
        $topTieCount = $topCash === null ? 0 : count(array_filter($finalists, static fn(array $team): bool =>
            abs((float) ($team['cash_balance'] ?? 0) - $topCash) < 0.00001
        ));
        $previousCash = null;
        $position = 0;

        foreach ($finalists as $index => &$team) {
            $cash = (float) ($team['cash_balance'] ?? 0);
            if ($previousCash === null || abs($cash - $previousCash) > 0.00001) {
                $position = $index + 1;
                $previousCash = $cash;
            }

            $team['final_position'] = $position;
            $team['final_cash_score'] = $cash;
            $team['display_status'] = $finalClosed
                ? ($position === 1 ? ($topTieCount > 1 ? 'Vencedor empatado' : 'Vencedor') : 'Finalista')
                : 'Classificado para a final';
        }
        unset($team);

        return $finalists;
    }

    private function projectorStatusForTeam(array $team): string
    {
        if (!empty($team['final_status'])) {
            return (string) $team['final_status'];
        }

        if (!empty($team['is_eliminated'])) {
            return 'Eliminado na semifinal';
        }

        if (!empty($team['qualified_for_final'])) {
            return 'Classificado para a final';
        }

        return 'Em jogo';
    }
}
