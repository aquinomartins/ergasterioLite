<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Session;
use App\Repositories\LiquidityActionRepository;
use App\Repositories\LiquidityEventRepository;
use App\Repositories\LiquidityGameRepository;
use App\Repositories\LiquidityParticipantRepository;
use App\Repositories\LiquidityRoundRepository;
use App\Repositories\LiquiditySessionRepository;
use App\Repositories\LiquidityTeamRepository;
use App\Repositories\LiquidityTradeProposalRepository;
use App\Repositories\UserRepository;
use App\Services\LiquidityPoolService;
use DomainException;
use PDO;

final class LiquidityGameController extends Controller
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function create(): void { $this->view('liquidity.games.create'); }

    public function store(): void
    {
        if (!Csrf::verifyFromRequest()) { Session::flash('error', 'CSRF inválido.'); $this->redirectTo('/liquidity/create'); }
        $title = trim((string) ($_POST['title'] ?? $_POST['name'] ?? ''));
        $maxRounds = max(1, (int) ($_POST['max_rounds'] ?? 6));
        $maxParticipants = ($_POST['max_participants'] ?? '') !== '' ? max(1, (int) $_POST['max_participants']) : null;
        if ($title === '') { Session::flash('error', 'Informe o título do jogo.'); $this->redirectTo('/liquidity/create'); }
        try {
            $this->pdo->beginTransaction();
            $inviteCode = $this->generateInviteCode();
            $session = (new LiquidityPoolService($this->pdo))->createSession(['name' => $title, 'access_code' => $inviteCode, 'total_rounds' => $maxRounds], Auth::id());
            $gameId = (int) $session['id'];
            (new LiquidityGameRepository($this->pdo))->create(['id' => $gameId, 'title' => $title, 'invite_code' => $inviteCode, 'owner_user_id' => Auth::id(), 'status' => 'waiting', 'mode' => 'individual', 'max_participants' => $maxParticipants, 'max_rounds' => $maxRounds, 'current_round' => 1]);
            $this->pdo->commit();
            Session::flash('success', 'Jogo criado. Compartilhe o código com os participantes.');
            $this->redirectTo('/liquidity/games/' . $gameId . '/teacher');
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) { $this->pdo->rollBack(); }
            Session::flash('error', 'Não foi possível criar o jogo agora.');
            $this->redirectTo('/liquidity/create');
        }
    }

    public function myGames(): void
    {
        try {
            $userId = (int) Auth::id();

            $ownedGames = (new LiquidityGameRepository($this->pdo))->getOwnedByUser($userId);
            $participantGames = (new LiquidityParticipantRepository($this->pdo))->getForUser($userId);

            $this->view('liquidity.games.mine', [
                'ownedGames' => $ownedGames,
                'participantGames' => $participantGames,
            ]);
        } catch (\Throwable $e) {
            error_log('[my-games] ' . $e->getMessage());
            error_log('[my-games trace] ' . $e->getTraceAsString());

            http_response_code(500);
            echo 'Não foi possível carregar seus jogos agora.';
        }
    }

    public function joinForm(): void { $this->view('liquidity.games.join'); }

    public function join(): void
    {
        if (!Csrf::verifyFromRequest()) { Session::flash('error', 'CSRF inválido.'); $this->redirectTo('/liquidity/join'); }
        $code = strtoupper(trim((string) ($_POST['invite_code'] ?? '')));
        $games = new LiquidityGameRepository($this->pdo); $participants = new LiquidityParticipantRepository($this->pdo); $game = $games->findByInviteCode($code);
        if (!$game) { Session::flash('error', 'Código de convite não encontrado.'); $this->redirectTo('/liquidity/join'); }
        $userId = (int) Auth::id();
        if (!$participants->findByGameAndUser((int) $game['id'], $userId)) {
            $maxParticipants = $game['max_participants'] !== null ? (int) $game['max_participants'] : null;
            if ($maxParticipants !== null && $participants->countActiveRequests((int) $game['id']) >= $maxParticipants) { Session::flash('error', 'Este jogo já atingiu o limite de participantes.'); $this->redirectTo('/liquidity/join'); }
            $participants->createPending((int) $game['id'], $userId);
        }
        Session::flash('success', 'Entrada solicitada. Aguarde a aprovação do professor.'); $this->redirectTo('/liquidity/my-games');
    }

    public function teacherPanel(string $gameId): void
    {
        $game = $this->requireOwnedGame((int) $gameId);
        $participants = new LiquidityParticipantRepository($this->pdo);
        $session = (new LiquiditySessionRepository($this->pdo))->findById((int)$game['id']) ?: [];
        $teams = $this->getTeamsForGame((int) $game['id']);
        $currentRound = (int)($game['current_round'] ?? $session['current_round'] ?? 1);
        $maxRounds = (int)($game['max_rounds'] ?? $session['total_rounds'] ?? 6);
        $values = $this->arenaValuationRules($session);
        $actionRepo = new LiquidityActionRepository($this->pdo);
        $actedByTeam = [];
        $lastActionByTeam = [];
        $decidedTeams = [];
        $pendingTeams = [];

        foreach ($teams as &$team) {
            $teamId = (int)($team['id'] ?? 0);
            $team['cash_balance'] = (float)($team['cash_balance'] ?? $team['cash'] ?? 0);
            $team['btc_balance'] = (float)($team['btc_balance'] ?? $team['btc'] ?? 0);
            $team['nft_balance'] = (int)($team['nft_balance'] ?? $team['nft_in_hand'] ?? 0);
            $team['pool_shares'] = (int)($team['pool_shares'] ?? 0);
            $team['estimated_wealth'] = $team['cash_balance']
                + ($team['btc_balance'] * $values['btc_value'])
                + ($team['nft_balance'] * $values['nft_value'])
                + ($team['pool_shares'] * $values['share_value']);

            $hasActed = $actionRepo->hasTeamActed((int)$game['id'], $currentRound, $teamId);
            $actedByTeam[$teamId] = $hasActed;
            $lastActionByTeam[$teamId] = $this->getLastActionForTeam((int)$game['id'], $teamId);

            if (($team['status'] ?? 'active') === 'active' && (int)($team['is_eliminated'] ?? 0) === 0) {
                if ($hasActed) {
                    $decidedTeams[] = $team;
                } else {
                    $pendingTeams[] = $team;
                }
            }
        }
        unset($team);

        $activeTeamsCount = count($decidedTeams) + count($pendingTeams);
        $actedCount = count($decidedTeams);
        $roundStats = [
            'current_round' => $currentRound,
            'active_teams' => $activeTeamsCount,
            'acted' => $actedCount,
            'pending' => count($pendingTeams),
            'percent' => $activeTeamsCount > 0 ? (int)round(($actedCount / $activeTeamsCount) * 100) : 0,
            'decided_teams' => $decidedTeams,
            'pending_teams' => $pendingTeams,
        ];

        $totalShares = array_sum(array_map(static fn($team): int => (int)($team['pool_shares'] ?? 0), $teams));
        $poolValue = $totalShares * $values['share_value'];
        $estimatedDividendTotal = $poolValue * $values['pool_yield_rate'];
        $poolStats = [
            'maintenance_fee' => (float)($session['round_fee'] ?? 100),
            'total_shares' => $totalShares,
            'pool_value' => $poolValue,
            'estimated_dividend_total' => $estimatedDividendTotal,
            'estimated_dividend_per_share' => $totalShares > 0 ? $estimatedDividendTotal / $totalShares : 0,
        ];

        $events = array_map(fn($event) => $this->humanizeArenaEvent($event), (new LiquidityEventRepository($this->pdo))->getRecentBySession((int)$game['id'], 30));

        $this->view('liquidity.games.teacher', [
            'game' => $game,
            'pendingParticipants' => $participants->getByGame((int) $game['id'], 'pending'),
            'approvedParticipants' => $participants->getByGame((int) $game['id'], 'approved'),
            'rejectedParticipants' => array_merge($participants->getByGame((int) $game['id'], 'rejected'), $participants->getByGame((int) $game['id'], 'removed')),
            'teams' => $teams,
            'actionsThisRound' => $actedByTeam,
            'actedByTeam' => $actedByTeam,
            'lastActionByTeam' => $lastActionByTeam,
            'currentRoundState' => (new LiquidityRoundRepository($this->pdo))->getCurrentRound((int) $game['id'], $currentRound) ?: ['status' => 'open'],
            'roundStats' => $roundStats,
            'poolStats' => $poolStats,
            'pendingProposals' => $this->getTeacherTradeProposals((int)$game['id']),
            'recentEvents' => $events,
            'events' => $events,
            'currentRound' => $currentRound,
            'maxRounds' => $maxRounds,
            'statusLabel' => $this->arenaStatusLabel((string)($game['status'] ?? 'waiting')),
            'valuationRules' => $values,
        ]);
    }

    public function approve(string $gameId, string $participantId): void
    {
        if (!Csrf::verifyFromRequest()) { Session::flash('error', 'CSRF inválido.'); $this->redirectTo('/liquidity/games/' . (int)$gameId . '/teacher'); }
        $game = $this->requireOwnedGame((int) $gameId); $participants = new LiquidityParticipantRepository($this->pdo); $participant = $participants->findInGame((int) $game['id'], (int) $participantId);
        if (!$participant) { http_response_code(404); echo 'Participante não encontrado.'; return; }
        $teamId = (int) ($participant['team_id'] ?? 0); if ($teamId <= 0) { $teamId = $this->createIndividualTeam($game, (int) $participant['user_id']); }
        $participants->approve((int) $participant['id'], $teamId); Session::flash('success', 'Participante aprovado e equipe criada.'); $this->redirectTo('/liquidity/games/' . (int) $game['id'] . '/teacher');
    }

    public function reject(string $gameId, string $participantId): void
    {
        if (!Csrf::verifyFromRequest()) { Session::flash('error', 'CSRF inválido.'); $this->redirectTo('/liquidity/games/' . (int)$gameId . '/teacher'); }
        $game = $this->requireOwnedGame((int) $gameId); $participant = (new LiquidityParticipantRepository($this->pdo))->findInGame((int) $game['id'], (int) $participantId); if ($participant) { (new LiquidityParticipantRepository($this->pdo))->reject((int) $participant['id']); }
        Session::flash('success', 'Solicitação rejeitada.'); $this->redirectTo('/liquidity/games/' . (int) $game['id'] . '/teacher');
    }

    public function myTeam(string $gameId): void
    {
        $game = (new LiquidityGameRepository($this->pdo))->findById((int) $gameId);
        if (!$game) { http_response_code(404); echo 'Jogo não encontrado.'; return; }

        $currentRound = (int)($game['current_round'] ?? 1);
        $safeData = [
            'game' => $game,
            'participant' => [],
            'team' => [],
            'currentRound' => $currentRound,
            'hasActionThisRound' => false,
            'hasActed' => false,
            'availableTeams' => [],
            'teams' => [],
            'receivedProposals' => [],
            'sentProposals' => [],
            'recentEvents' => [],
            'tradeHistory' => [],
            'roundState' => ['status' => 'open'],
            'lastAction' => null,
            'lastDividend' => null,
        ];

        $participant = (new LiquidityParticipantRepository($this->pdo))->findByGameAndUser((int) $game['id'], (int) Auth::id());
        if (!$participant) { http_response_code(403); echo 'Você não participa deste jogo.'; return; }

        $safeData['participant'] = $participant;
        if (($participant['status'] ?? '') !== 'approved') {
            $messages = [
                'pending' => 'Sua entrada ainda aguarda aprovação do professor.',
                'rejected' => 'Sua solicitação para este jogo foi recusada.',
                'removed' => 'Você não participa mais deste jogo.',
            ];
            $safeData['message'] = $messages[(string)($participant['status'] ?? '')] ?? 'Sua participação não está aprovada para este jogo.';
            $this->view('liquidity.games.my_team', $safeData);
            return;
        }

        if (empty($participant['team_id'])) {
            $safeData['message'] = 'Sua participação foi aprovada, mas sua equipe ainda não foi vinculada. Avise o professor.';
            $this->view('liquidity.games.my_team', $safeData);
            return;
        }

        $team = (new LiquidityTeamRepository($this->pdo))->findById((int) $participant['team_id']);
        if (!$team || !$this->teamBelongsToGame($team, (int)$game['id'])) {
            $safeData['message'] = 'Sua participação está vinculada a uma equipe inválida. Avise o professor.';
            $this->view('liquidity.games.my_team', $safeData);
            return;
        }

        $actionRepo = new LiquidityActionRepository($this->pdo);
        $tradeRepo = new LiquidityTradeProposalRepository($this->pdo);
        $hasActionThisRound = $actionRepo->hasTeamActed((int)$game['id'], $currentRound, (int)$team['id']);
        $availableTeams = $this->getOtherTeamsForGame((int)$game['id'], (int)$team['id']);

        $this->view('liquidity.games.my_team', array_merge($safeData, [
            'team' => $team,
            'hasActionThisRound' => $hasActionThisRound,
            'hasActed' => $hasActionThisRound,
            'roundState' => (new LiquidityRoundRepository($this->pdo))->getCurrentRound((int)$game['id'], $currentRound) ?: ['status' => 'open'],
            'lastAction' => $this->getLastActionForTeam((int)$game['id'], (int)$team['id']),
            'lastDividend' => $this->getLastDividendForTeam((int)$game['id'], (int)$team['id']),
            'availableTeams' => $availableTeams,
            'teams' => $availableTeams,
            'sentProposals' => $tradeRepo->sent((int)$game['id'], (int)$team['id']),
            'receivedProposals' => $tradeRepo->received((int)$game['id'], (int)$team['id']),
            'recentEvents' => (new LiquidityEventRepository($this->pdo))->getRecentBySession((int)$game['id'], 30),
            'tradeHistory' => $tradeRepo->history((int)$game['id'], (int)$team['id']),
        ]));
    }

    public function submitMyTeamAction(string $gameId): void
    {
        if (!Csrf::verifyFromRequest()) { Session::flash('error', 'CSRF inválido.'); $this->redirectTo('/liquidity/games/' . (int)$gameId . '/my-team'); }
        $game = (new LiquidityGameRepository($this->pdo))->findById((int)$gameId); if (!$game) { http_response_code(404); echo 'Jogo não encontrado.'; return; }
        $participant = (new LiquidityParticipantRepository($this->pdo))->findByGameAndUser((int)$game['id'], (int)Auth::id());
        if (!$participant || $participant['status'] !== 'approved' || empty($participant['team_id'])) { http_response_code(403); echo 'Acesso negado.'; return; }
        $team = (new LiquidityTeamRepository($this->pdo))->findById((int)$participant['team_id']); if (!$team || !$this->teamBelongsToGame($team, (int)$game['id'])) { http_response_code(403); echo 'Equipe inválida.'; return; }
        $action = (string)($_POST['action_type'] ?? '');
        if (!in_array($action, ['deposit_nft', 'withdraw_nft_btc', 'withdraw_nft_cash', 'pass'], true)) { Session::flash('error', 'Ação inválida.'); $this->redirectTo('/liquidity/games/' . (int)$game['id'] . '/my-team'); }
        try {
            $roundState = (new LiquidityRoundRepository($this->pdo))->getCurrentRound((int)$game['id'], (int)$game['current_round']);
            if (($roundState['status'] ?? 'open') !== 'open') { throw new DomainException('Rodada encerrada. Aguarde o professor iniciar a próxima rodada.'); }
            (new LiquidityPoolService($this->pdo))->submitTeamAction((int)$game['id'], (int)$team['id'], $action, max(1, (float)($_POST['quantity'] ?? 1)));
            $messages = ['deposit_nft' => 'Você depositou 1 NFT na piscina, recebeu 10 BTC e ganhou 1 cota.', 'withdraw_nft_btc' => 'Você retirou 1 NFT da piscina pagando 11 BTC.', 'withdraw_nft_cash' => 'Você retirou 1 NFT da piscina pagando R$ 2.000.', 'pass' => 'Sua equipe passou a vez nesta rodada.'];
            Session::flash('success', $messages[$action]);
        } catch (DomainException $e) {
            Session::flash('error', str_replace('Este time já usou sua ação nesta rodada.', 'Sua equipe já realizou uma ação nesta rodada.', $e->getMessage()));
        } catch (\Throwable $e) { Session::flash('error', 'Não foi possível registrar a ação agora.'); }
        $this->redirectTo('/liquidity/games/' . (int)$game['id'] . '/my-team');
    }


    public function createTradeProposal(string $gameId): void
    {
        if (!Csrf::verifyFromRequest()) { Session::flash('error', 'CSRF inválido.'); $this->redirectTo('/liquidity/games/' . (int)$gameId . '/my-team'); }
        [$game, $participant, $team] = $this->requireApprovedParticipantTeam((int)$gameId);
        try {
            (new LiquidityPoolService($this->pdo))->createTradeProposal((int)$game['id'], (int)$team['id'], (int)($_POST['counterparty_team_id'] ?? 0), (int)Auth::id(), (string)($_POST['action_type'] ?? ''), (string)($_POST['asset_type'] ?? ''), (float)($_POST['quantity'] ?? 0), (float)($_POST['unit_price'] ?? 0));
            Session::flash('success', 'Proposta enviada para aprovação da contraparte.');
        } catch (DomainException $e) { Session::flash('error', $e->getMessage()); }
        $this->redirectTo('/liquidity/games/' . (int)$game['id'] . '/my-team');
    }
    public function approveTradeProposal(string $gameId, string $proposalId): void
    {
        if (!Csrf::verifyFromRequest()) { Session::flash('error', 'CSRF inválido.'); $this->redirectTo('/liquidity/games/' . (int)$gameId . '/my-team'); }
        [$game, $participant, $team] = $this->requireApprovedParticipantTeam((int)$gameId);
        $proposal=(new LiquidityTradeProposalRepository($this->pdo))->findInGame((int)$game['id'],(int)$proposalId);
        if(!$proposal || (int)$proposal['counterparty_team_id'] !== (int)$team['id']){http_response_code(403); echo 'Acesso negado.'; return;}
        try{(new LiquidityPoolService($this->pdo))->approveTradeProposal((int)$game['id'],(int)$proposalId);Session::flash('success','Transação aprovada e executada.');}catch(DomainException $e){Session::flash('error',$e->getMessage());}
        $this->redirectTo('/liquidity/games/' . (int)$game['id'] . '/my-team');
    }
    public function rejectTradeProposal(string $gameId, string $proposalId): void
    {
        if (!Csrf::verifyFromRequest()) { Session::flash('error', 'CSRF inválido.'); $this->redirectTo('/liquidity/games/' . (int)$gameId . '/my-team'); }
        [$game, $participant, $team] = $this->requireApprovedParticipantTeam((int)$gameId);
        $proposal=(new LiquidityTradeProposalRepository($this->pdo))->findInGame((int)$game['id'],(int)$proposalId);
        if(!$proposal || (int)$proposal['counterparty_team_id'] !== (int)$team['id']){http_response_code(403); echo 'Acesso negado.'; return;}
        try{(new LiquidityPoolService($this->pdo))->rejectTradeProposal((int)$game['id'],(int)$proposalId);Session::flash('success','Proposta rejeitada.');}catch(DomainException $e){Session::flash('error',$e->getMessage());}
        $this->redirectTo('/liquidity/games/' . (int)$game['id'] . '/my-team');
    }
    private function requireApprovedParticipantTeam(int $gameId): array { $game=(new LiquidityGameRepository($this->pdo))->findById($gameId); if(!$game){http_response_code(404);exit('Jogo não encontrado.');}$participant=(new LiquidityParticipantRepository($this->pdo))->findByGameAndUser($gameId,(int)Auth::id()); if(!$participant||$participant['status']!=='approved'||empty($participant['team_id'])){http_response_code(403);exit('Acesso negado.');}$team=(new LiquidityTeamRepository($this->pdo))->findById((int)$participant['team_id']); if(!$team||!$this->teamBelongsToGame($team,$gameId)){http_response_code(403);exit('Equipe inválida.');} return [$game,$participant,$team]; }

    public function advanceGameRound(string $gameId): void
    {
        if (!Csrf::verifyFromRequest()) { Session::flash('error', 'CSRF inválido.'); $this->redirectTo('/liquidity/games/' . (int)$gameId . '/teacher'); }
        $game = $this->requireOwnedGame((int)$gameId);
        try { $result = (new LiquidityPoolService($this->pdo))->advanceRound((int)$game['id']); $session = $this->pdo->prepare('SELECT current_round, session_phase FROM liquidity_sessions WHERE id = ?'); $session->execute([(int)$game['id']]); $sessionRow = $session->fetch() ?: ['current_round' => $result['next_round'], 'session_phase' => 'regular']; $status = ($sessionRow['session_phase'] ?? 'regular') === 'semifinal' ? 'semifinal' : 'active'; $this->pdo->prepare('UPDATE liquidity_games SET current_round = ?, status = ?, updated_at = NOW() WHERE id = ?')->execute([(int)$sessionRow['current_round'], $status, (int)$game['id']]); Session::flash('success', 'Rodada encerrada com taxa e dividendos aplicados.'); }
        catch (DomainException $e) { Session::flash('error', $e->getMessage()); }
        catch (\Throwable $e) { Session::flash('error', 'Não foi possível avançar a rodada agora.'); }
        $this->redirectTo('/liquidity/games/' . (int)$game['id'] . '/teacher');
    }


    public function arenas(): void
    {
        $this->view('liquidity.games.arenas', [
            'games' => (new LiquidityGameRepository($this->pdo))->getPublicArenas(),
        ]);
    }

    public function arena(string $gameId): void
    {
        try {
            $game = (new LiquidityGameRepository($this->pdo))->findById((int)$gameId);
            if (!$game) {
                http_response_code(404);
                echo 'Jogo não encontrado.';
                return;
            }

            $session = (new LiquiditySessionRepository($this->pdo))->findById((int)$game['id']) ?: [];
            $teams = $this->getTeamsForGame((int)$game['id']);
            $currentRound = (int)($game['current_round'] ?? $session['current_round'] ?? 1);
            $maxRounds = (int)($game['max_rounds'] ?? $session['total_rounds'] ?? 6);
            $values = $this->arenaValuationRules($session);
            $actionRepo = new LiquidityActionRepository($this->pdo);
            $actedByTeam = [];
            $lastActionByTeam = [];
            $decisions = [];

            foreach ($teams as &$team) {
                $teamId = (int)($team['id'] ?? 0);
                $team['cash_balance'] = (float)($team['cash_balance'] ?? $team['cash'] ?? 0);
                $team['btc_balance'] = (float)($team['btc_balance'] ?? $team['btc'] ?? 0);
                $team['nft_balance'] = (int)($team['nft_balance'] ?? $team['nft_in_hand'] ?? 0);
                $team['pool_shares'] = (int)($team['pool_shares'] ?? 0);
                $team['estimated_wealth'] = $team['cash_balance']
                    + ($team['btc_balance'] * $values['btc_value'])
                    + ($team['nft_balance'] * $values['nft_value'])
                    + ($team['pool_shares'] * $values['share_value']);

                $hasActed = $actionRepo->hasTeamActed((int)$game['id'], $currentRound, $teamId);
                $lastAction = $this->getLastActionForTeam((int)$game['id'], $teamId);
                $actedByTeam[$teamId] = $hasActed;
                $lastActionByTeam[$teamId] = $lastAction;
                $decisions[] = [
                    'team_name' => (string)($team['name'] ?? 'Equipe'),
                    'has_acted' => $hasActed,
                    'last_action_label' => $lastAction ? $this->arenaActionLabel((string)($lastAction['action_type'] ?? '')) : null,
                ];
            }
            unset($team);

            usort($teams, fn($a, $b) => ((float)$b['estimated_wealth'] <=> (float)$a['estimated_wealth']) ?: ((string)$a['name'] <=> (string)$b['name']));

            $totalShares = array_sum(array_map(static fn($team): int => (int)($team['pool_shares'] ?? 0), $teams));
            $poolValue = $totalShares * $values['share_value'];
            $estimatedDividendTotal = $poolValue * $values['pool_yield_rate'];
            $estimatedDividendPerShare = $totalShares > 0 ? $estimatedDividendTotal / $totalShares : 0.0;
            $actedCount = count(array_filter($actedByTeam));
            $events = array_map(fn($event) => $this->humanizeArenaEvent($event), (new LiquidityEventRepository($this->pdo))->getRecentBySession((int)$game['id'], 30));
            $executedTrades = (new LiquidityTradeProposalRepository($this->pdo))->publicHistory((int)$game['id'], 12);

            $this->view('liquidity.games.arena', [
                'game' => $game,
                'teams' => $teams,
                'ranking' => $teams,
                'actedByTeam' => $actedByTeam,
                'lastActionByTeam' => $lastActionByTeam,
                'decisions' => $decisions,
                'poolStats' => [
                    'total_shares' => $totalShares,
                    'pool_value' => $poolValue,
                    'estimated_dividend_total' => $estimatedDividendTotal,
                    'estimated_dividend_per_share' => $estimatedDividendPerShare,
                    'estimated_pool_nfts' => $totalShares,
                ],
                'poolMetrics' => [
                    'total_shares' => $totalShares,
                    'pool_value' => $poolValue,
                    'estimated_dividend_total' => $estimatedDividendTotal,
                    'estimated_dividend_per_share' => $estimatedDividendPerShare,
                ],
                'roundStats' => ['acted' => $actedCount, 'total' => count($teams)],
                'recentEvents' => $events,
                'events' => $events,
                'executedTrades' => $executedTrades,
                'currentRound' => $currentRound,
                'maxRounds' => $maxRounds,
                'statusLabel' => $this->arenaStatusLabel((string)($game['status'] ?? 'waiting')),
                'valuationRules' => $values,
            ]);
        } catch (\Throwable $e) {
            error_log('[arena] ' . $e->getMessage());
            http_response_code(500);
            echo 'Não foi possível carregar esta arena no momento.';
        }
    }


    private function getTeacherTradeProposals(int $gameId): array
    {
        $stmt = $this->pdo->prepare("SELECT p.*, pt.name proposer_name, ct.name counterparty_name FROM liquidity_trade_proposals p LEFT JOIN liquidity_teams pt ON pt.id = p.proposer_team_id LEFT JOIN liquidity_teams ct ON ct.id = p.counterparty_team_id WHERE p.game_id = ? ORDER BY p.id DESC LIMIT 50");
        $stmt->execute([$gameId]);
        return $stmt->fetchAll();
    }
    private function arenaValuationRules(array $session): array
    {
        return [
            'btc_value' => (float)($session['btc_sell_price'] ?? 100),
            'nft_value' => (float)($session['nft_pool_value'] ?? 2000),
            'share_value' => (float)($session['nft_pool_value'] ?? 2000),
            'pool_yield_rate' => (float)($session['pool_yield_rate'] ?? 0.10),
        ];
    }
    private function arenaStatusLabel(string $status): string { return ['draft'=>'Rascunho','waiting'=>'Aguardando jogadores','active'=>'Em andamento','semifinal'=>'Semifinal','final'=>'Final','finished'=>'Encerrado'][$status] ?? 'Em acompanhamento'; }
    private function arenaActionLabel(string $action): string { return ['deposit_nft'=>'Depositou NFT','withdraw_nft_btc'=>'Retirou NFT com BTC','withdraw_nft_cash'=>'Retirou NFT com caixa','pass'=>'Passou a vez','buy_btc'=>'Comprou BTC','sell_btc'=>'Vendeu BTC'][$action] ?? 'Decisão registrada'; }
    private function humanizeArenaEvent(array $event): array
    {
        $description = trim((string)($event['description'] ?? ''));
        if ($description === '') {
            $description = ['deposit_nft'=>'Equipe depositou NFT na piscina.','withdraw_nft_btc'=>'Equipe retirou NFT usando BTC.','withdraw_nft_cash'=>'Equipe retirou NFT usando caixa.','pool_dividend'=>'Dividendos da piscina foram distribuídos.','round_advanced'=>'Rodada avançada.','trade_executed'=>'Transação executada entre equipes.','trade_master_executed'=>'Transação executada pelo Master Gold.'][(string)($event['event_type'] ?? '')] ?? 'Evento registrado no jogo.';
        }
        $event['description'] = $description;
        return $event;
    }
    private function isMasterGold(): bool { $u = Auth::user(); return strtolower((string)($u['role'] ?? '')) === 'master_gold'; }
    private function requireOwnedGame(int $gameId): array { $game = (new LiquidityGameRepository($this->pdo))->findById($gameId); if (!$game) { http_response_code(404); exit('Jogo não encontrado.'); } if (!$this->isMasterGold() && (int) $game['owner_user_id'] !== (int) Auth::id()) { http_response_code(403); exit('Acesso negado.'); } return $game; }
    private function generateInviteCode(): string { $repo = new LiquidityGameRepository($this->pdo); do { $code = 'PL-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6)); } while ($repo->inviteCodeExists($code)); return $code; }
    private function createIndividualTeam(array $game, int $userId): int { $user = (new UserRepository($this->pdo))->findWithProfileById($userId) ?? ['id' => $userId]; $baseName = trim((string) ($user['display_name'] ?? $user['username'] ?? '')); $name = 'Equipe ' . ($baseName !== '' ? $baseName : 'Usuário #' . $userId); $loginCode = strtoupper(substr(bin2hex(random_bytes(3)), 0, 6)); $statement = $this->pdo->prepare('INSERT INTO liquidity_teams (session_id, game_id, name, login_code, cash_balance, btc_balance, nft_balance, pool_shares, status, created_at) VALUES (:session_id, :game_id, :name, :login_code, 1600.00, 0.00, 1, 0, \'active\', NOW())'); $statement->execute(['session_id' => (int) $game['id'], 'game_id' => (int) $game['id'], 'name' => $name, 'login_code' => $loginCode]); return (int) $this->pdo->lastInsertId(); }
    private function getOtherTeamsForGame(int $gameId, int $teamId): array { $teams=$this->getTeamsForGame($gameId); return array_values(array_filter($teams, static fn($t) => (int)$t['id'] !== $teamId)); }
    private function getTeamsForGame(int $gameId): array { $stmt = $this->pdo->prepare('SELECT * FROM liquidity_teams WHERE game_id = ? OR (game_id IS NULL AND session_id = ?) ORDER BY id'); $stmt->execute([$gameId, $gameId]); return $stmt->fetchAll(); }
    private function teamBelongsToGame(array $team, int $gameId): bool { $teamGameId = isset($team['game_id']) ? (int)$team['game_id'] : 0; return $teamGameId > 0 ? $teamGameId === $gameId : (int)($team['session_id'] ?? 0) === $gameId; }
    private function getLastActionForTeam(int $sessionId, int $teamId): ?array { $stmt = $this->pdo->prepare('SELECT * FROM liquidity_team_actions WHERE session_id = ? AND team_id = ? ORDER BY id DESC LIMIT 1'); $stmt->execute([$sessionId, $teamId]); $row = $stmt->fetch(); return $row ?: null; }
    private function getLastDividendForTeam(int $sessionId, int $teamId): ?array { $stmt = $this->pdo->prepare("SELECT * FROM liquidity_events WHERE session_id = ? AND team_id = ? AND event_type = 'pool_dividend' ORDER BY id DESC LIMIT 1"); $stmt->execute([$sessionId, $teamId]); $row = $stmt->fetch(); return $row ?: null; }
}
