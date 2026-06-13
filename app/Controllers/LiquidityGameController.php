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
use App\Repositories\LiquidityTeamRepository;
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
        $userId = (int) Auth::id();
        $this->view('liquidity.games.mine', ['ownedGames' => (new LiquidityGameRepository($this->pdo))->getOwnedByUser($userId), 'participantGames' => (new LiquidityParticipantRepository($this->pdo))->getForUser($userId)]);
    }

    public function joinForm(): void { $this->view('liquidity.games.join'); }

    public function join(): void
    {
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
        $teams = $this->getTeamsForGame((int) $game['id']);
        $round = (int) $game['current_round'];
        $actionRepo = new LiquidityActionRepository($this->pdo);
        $actedByTeam = []; $lastActionByTeam = [];
        foreach ($teams as $team) { $tid = (int) $team['id']; $actedByTeam[$tid] = $actionRepo->hasTeamActed((int) $game['id'], $round, $tid); $lastActionByTeam[$tid] = $this->getLastActionForTeam((int) $game['id'], $tid); }
        $this->view('liquidity.games.teacher', ['game' => $game, 'pendingParticipants' => $participants->getByGame((int) $game['id'], 'pending'), 'approvedParticipants' => $participants->getByGame((int) $game['id'], 'approved'), 'rejectedParticipants' => $participants->getByGame((int) $game['id'], 'rejected'), 'teams' => $teams, 'actedByTeam' => $actedByTeam, 'lastActionByTeam' => $lastActionByTeam, 'events' => (new LiquidityEventRepository($this->pdo))->getRecentBySession((int) $game['id'], 30)]);
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
        $game = (new LiquidityGameRepository($this->pdo))->findById((int) $gameId); if (!$game) { http_response_code(404); echo 'Jogo não encontrado.'; return; }
        $participant = (new LiquidityParticipantRepository($this->pdo))->findByGameAndUser((int) $game['id'], (int) Auth::id()); if (!$participant) { http_response_code(403); echo 'Você não participa deste jogo.'; return; }
        if ($participant['status'] !== 'approved') { $message = $participant['status'] === 'rejected' ? 'Sua solicitação para este jogo foi recusada.' : 'Sua entrada ainda aguarda aprovação do professor.'; $this->view('liquidity.games.my_team', ['game' => $game, 'participant' => $participant, 'message' => $message]); return; }
        $team = (new LiquidityTeamRepository($this->pdo))->findById((int) $participant['team_id']); if (!$team || (int)($team['game_id'] ?? $team['session_id']) !== (int)$game['id']) { http_response_code(403); echo 'Equipe inválida.'; return; }
        $actionRepo = new LiquidityActionRepository($this->pdo); $round = (int)$game['current_round'];
        $this->view('liquidity.games.my_team', ['game' => $game, 'participant' => $participant, 'team' => $team, 'hasActed' => $actionRepo->hasTeamActed((int)$game['id'], $round, (int)$team['id']), 'lastAction' => $this->getLastActionForTeam((int)$game['id'], (int)$team['id'])]);
    }

    public function submitMyTeamAction(string $gameId): void
    {
        if (!Csrf::verifyFromRequest()) { Session::flash('error', 'CSRF inválido.'); $this->redirectTo('/liquidity/games/' . (int)$gameId . '/my-team'); }
        $game = (new LiquidityGameRepository($this->pdo))->findById((int)$gameId); if (!$game) { http_response_code(404); echo 'Jogo não encontrado.'; return; }
        $participant = (new LiquidityParticipantRepository($this->pdo))->findByGameAndUser((int)$game['id'], (int)Auth::id());
        if (!$participant || $participant['status'] !== 'approved' || empty($participant['team_id'])) { http_response_code(403); echo 'Acesso negado.'; return; }
        $team = (new LiquidityTeamRepository($this->pdo))->findById((int)$participant['team_id']); if (!$team || (int)($team['game_id'] ?? $team['session_id']) !== (int)$game['id']) { http_response_code(403); echo 'Equipe inválida.'; return; }
        $action = (string)($_POST['action_type'] ?? '');
        if (!in_array($action, ['deposit_nft', 'withdraw_nft_btc', 'withdraw_nft_cash', 'pass'], true)) { Session::flash('error', 'Ação inválida.'); $this->redirectTo('/liquidity/games/' . (int)$game['id'] . '/my-team'); }
        try {
            (new LiquidityPoolService($this->pdo))->submitTeamAction((int)$game['id'], (int)$team['id'], $action, 1.0);
            $messages = ['deposit_nft' => 'Você depositou 1 NFT na piscina, recebeu 10 BTC e ganhou 1 cota.', 'withdraw_nft_btc' => 'Você retirou 1 NFT da piscina pagando 11 BTC.', 'withdraw_nft_cash' => 'Você retirou 1 NFT da piscina pagando R$ 2.000.', 'pass' => 'Sua equipe passou a vez nesta rodada.'];
            Session::flash('success', $messages[$action]);
        } catch (DomainException $e) {
            Session::flash('error', str_replace('Este time já usou sua ação nesta rodada.', 'Sua equipe já realizou uma ação nesta rodada.', $e->getMessage()));
        } catch (\Throwable $e) { Session::flash('error', 'Não foi possível registrar a ação agora.'); }
        $this->redirectTo('/liquidity/games/' . (int)$game['id'] . '/my-team');
    }

    public function advanceGameRound(string $gameId): void
    {
        if (!Csrf::verifyFromRequest()) { Session::flash('error', 'CSRF inválido.'); $this->redirectTo('/liquidity/games/' . (int)$gameId . '/teacher'); }
        $game = $this->requireOwnedGame((int)$gameId);
        try { (new LiquidityPoolService($this->pdo))->advanceRound((int)$game['id']); $this->pdo->prepare('UPDATE liquidity_games SET current_round = current_round + 1, status = IF(status = \'waiting\', \'active\', status), updated_at = NOW() WHERE id = ?')->execute([(int)$game['id']]); Session::flash('success', 'Rodada avançada com sucesso.'); }
        catch (DomainException $e) { Session::flash('error', $e->getMessage()); }
        catch (\Throwable $e) { Session::flash('error', 'Não foi possível avançar a rodada agora.'); }
        $this->redirectTo('/liquidity/games/' . (int)$game['id'] . '/teacher');
    }

    public function arena(string $gameId): void
    {
        $game = (new LiquidityGameRepository($this->pdo))->findById((int)$gameId); if (!$game) { http_response_code(404); echo 'Jogo não encontrado.'; return; }
        $teams = $this->getTeamsForGame((int)$game['id']); $round = (int)$game['current_round']; $actionRepo = new LiquidityActionRepository($this->pdo); $actedByTeam = [];
        foreach ($teams as &$team) { $team['estimated_wealth'] = (float)$team['cash_balance'] + ((float)$team['btc_balance'] * 100) + ((int)$team['nft_balance'] * 2000) + ((int)$team['pool_shares'] * 2000); $actedByTeam[(int)$team['id']] = $actionRepo->hasTeamActed((int)$game['id'], $round, (int)$team['id']); } unset($team);
        usort($teams, fn($a, $b) => ((float)$b['estimated_wealth'] <=> (float)$a['estimated_wealth']) ?: ((string)$a['name'] <=> (string)$b['name']));
        $this->view('liquidity.games.arena', ['game' => $game, 'teams' => $teams, 'actedByTeam' => $actedByTeam, 'events' => (new LiquidityEventRepository($this->pdo))->getRecentBySession((int)$game['id'], 30)]);
    }

    private function requireOwnedGame(int $gameId): array { $game = (new LiquidityGameRepository($this->pdo))->findById($gameId); if (!$game) { http_response_code(404); exit('Jogo não encontrado.'); } if ((int) $game['owner_user_id'] !== (int) Auth::id()) { http_response_code(403); exit('Acesso negado.'); } return $game; }
    private function generateInviteCode(): string { $repo = new LiquidityGameRepository($this->pdo); do { $code = 'PL-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6)); } while ($repo->inviteCodeExists($code)); return $code; }
    private function createIndividualTeam(array $game, int $userId): int { $user = (new UserRepository($this->pdo))->findWithProfileById($userId) ?? ['id' => $userId]; $baseName = trim((string) ($user['display_name'] ?? $user['username'] ?? '')); $name = 'Equipe ' . ($baseName !== '' ? $baseName : 'Usuário #' . $userId); $loginCode = strtoupper(substr(bin2hex(random_bytes(3)), 0, 6)); $statement = $this->pdo->prepare('INSERT INTO liquidity_teams (session_id, game_id, name, login_code, cash_balance, btc_balance, nft_balance, pool_shares, status, created_at) VALUES (:session_id, :game_id, :name, :login_code, 1600.00, 0.00, 1, 0, \'active\', NOW())'); $statement->execute(['session_id' => (int) $game['id'], 'game_id' => (int) $game['id'], 'name' => $name, 'login_code' => $loginCode]); return (int) $this->pdo->lastInsertId(); }
    private function getTeamsForGame(int $gameId): array { $stmt = $this->pdo->prepare('SELECT * FROM liquidity_teams WHERE game_id = ? OR (game_id IS NULL AND session_id = ?) ORDER BY id'); $stmt->execute([$gameId, $gameId]); return $stmt->fetchAll(); }
    private function getLastActionForTeam(int $sessionId, int $teamId): ?array { $stmt = $this->pdo->prepare('SELECT * FROM liquidity_team_actions WHERE session_id = ? AND team_id = ? ORDER BY id DESC LIMIT 1'); $stmt->execute([$sessionId, $teamId]); $row = $stmt->fetch(); return $row ?: null; }
}
