<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Session;
use App\Requests\StorePositionRequest;
use App\Services\MarketService;
use App\Services\PositionService;
use DomainException;
use JsonException;
use Throwable;

final class PositionController extends Controller
{
    private PositionService $positions;
    private MarketService $markets;

    public function __construct()
    {
        $this->positions = new PositionService();
        $this->markets = new MarketService();
    }

    public function store(string $id): void
    {
        [$errors, $data] = (new StorePositionRequest())->validate($_POST);

        $market = $this->markets->getMarketById((int) $id);

        if ($market === null) {
            Session::flash('error', 'Mercado não encontrado.');
            $this->redirectTo('/markets');
        }

        $redirectPath = '/markets/' . $market['slug'];

        if ($errors !== []) {
            Session::set('errors', $errors);
            Session::flash('error', 'Revise os dados da participação.');
            $this->redirectTo($redirectPath);
        }

        $userId = Auth::id();

        if ($userId === null) {
            Session::flash('error', 'Faça login para participar de um mercado.');
            $this->redirectTo('/login');
        }

        try {
            $this->positions->openPosition($userId, (int) $id, (int) $data['option_id'], (float) $data['shares_amount']);
            Session::flash('success', 'Participação registrada com sucesso. Probabilidades atualizadas.');
        } catch (DomainException $exception) {
            Session::set('errors', ['position' => [$exception->getMessage()]]);
            Session::flash('error', $exception->getMessage());
        } catch (Throwable $exception) {
            $this->logParticipationError($exception, [
                'user_id' => $userId,
                'market_id' => (int) $id,
                'option_id' => (int) ($data['option_id'] ?? 0),
                'shares_amount' => (float) ($data['shares_amount'] ?? 0),
                'path' => $_SERVER['REQUEST_URI'] ?? '',
            ]);
            Session::set('errors', ['position' => ['Não foi possível registrar a participação agora. Tente novamente.']]);
            Session::flash('error', 'Ocorreu um erro ao processar sua participação.');
        }

        $this->redirectTo($redirectPath);
    }

    private function logParticipationError(Throwable $exception, array $context): void
    {
        try {
            $contextJson = json_encode($context, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (JsonException $jsonException) {
            $contextJson = '{}';
        }

        error_log(sprintf(
            '[position.store] %s | context=%s | exception=%s',
            $exception->getMessage(),
            $contextJson,
            $exception->getTraceAsString()
        ));
    }
}
