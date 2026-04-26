<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Session;
use App\Policies\MarketPolicy;
use App\Requests\ResolveMarketRequest;
use App\Requests\StoreMarketRequest;
use App\Services\MarketService;
use App\Services\PositionService;
use App\Services\ResolutionService;
use App\Repositories\MarketResolutionRepository;
use App\Repositories\PayoutRepository;
use DomainException;

final class MarketController extends Controller
{
    private MarketService $markets;
    private PositionService $positions;
    private MarketPolicy $policy;
    private ResolutionService $resolutionService;
    private MarketResolutionRepository $resolutions;
    private PayoutRepository $payouts;

    public function __construct()
    {
        $this->markets = new MarketService();
        $this->positions = new PositionService();
        $this->policy = new MarketPolicy();
        $this->resolutionService = new ResolutionService();
        $this->resolutions = new MarketResolutionRepository();
        $this->payouts = new PayoutRepository();
    }

    public function index(): void
    {
        $this->view('markets.index', [
            'pageTitle' => 'Mercados',
            'markets' => $this->markets->listMarkets(),
            'openMarkets' => $this->markets->listOpenMarkets(),
            'canManageMarkets' => $this->policy->canManage(Auth::user()),
        ]);
    }

    public function show(string $slug): void
    {
        $market = $this->markets->getMarketBySlug($slug);

        if ($market === null) {
            http_response_code(404);
            echo 'Mercado não encontrado.';
            return;
        }

        $userId = Auth::id();
        $balance = $userId !== null ? $this->positions->getUserBalance($userId) : null;

        $this->view('markets.show', [
            'pageTitle' => (string) $market['title'],
            'market' => $market,
            'isAuthenticated' => Auth::check(),
            'canManageMarkets' => $this->policy->canManage(Auth::user()),
            'errors' => Session::get('errors', []),
            'userBalance' => $balance,
            'trades' => $this->positions->getMarketTrades((int) $market['id']),
            'resolution' => $this->resolutions->findByMarketId((int) $market['id']),
            'marketPayouts' => $this->payouts->getByMarketId((int) $market['id']),
            'myPayouts' => $userId !== null ? $this->payouts->getByUserId($userId) : [],
        ]);
        Session::forget('errors');
    }

    public function create(): void
    {
        if (! $this->policy->canCreate(Auth::user())) {
            Session::flash('error', 'Faça login para criar um mercado.');
            $this->redirectTo('/login');
        }

        $dependencies = $this->markets->getCreationDependencies();

        $this->view('markets.create', [
            'pageTitle' => 'Novo mercado',
            'old' => Session::get('old', []),
            'errors' => Session::get('errors', []),
            'artworks' => $dependencies['artworks'],
            'artists' => $dependencies['artists'],
        ]);
        Session::forget('old');
        Session::forget('errors');
    }

    public function store(): void
    {
        if (! $this->policy->canCreate(Auth::user())) {
            Session::flash('error', 'Faça login para criar um mercado.');
            $this->redirectTo('/login');
        }

        [$errors, $data] = (new StoreMarketRequest())->validate($_POST);

        if ($errors !== []) {
            Session::set('old', $data);
            Session::set('errors', $errors);
            Session::flash('error', 'Revise os dados do mercado.');
            $this->redirectTo('/markets/create');
        }

        try {
            $market = $this->markets->createMarket($data, Auth::id());
            Session::flash('success', 'Mercado criado com sucesso em modo rascunho.');
            $this->redirectTo('/markets/' . $market['slug']);
        } catch (DomainException $exception) {
            Session::set('old', $data);
            Session::set('errors', ['market' => [$exception->getMessage()]]);
            Session::flash('error', $exception->getMessage());
            $this->redirectTo('/markets/create');
        }
    }

    public function publish(string $id): void
    {
        $this->guardManager('publicar');

        try {
            $market = $this->markets->publishMarket((int) $id);
            Session::flash('success', 'Mercado publicado com sucesso.');
            $this->redirectTo('/markets/' . $market['slug']);
        } catch (DomainException $exception) {
            Session::flash('error', $exception->getMessage());
            $this->redirectTo('/markets');
        }
    }

    public function close(string $id): void
    {
        $this->guardManager('fechar');

        try {
            $actorId = Auth::id();
            if ($actorId === null) {
                throw new DomainException('Faça login para fechar mercados.');
            }

            $market = $this->resolutionService->closeMarket((int) $id, $actorId);
            Session::flash('success', 'Mercado fechado com sucesso.');
            $this->redirectTo('/markets/' . $market['slug']);
        } catch (DomainException $exception) {
            Session::flash('error', $exception->getMessage());
            $this->redirectTo('/markets');
        }
    }

    public function resolve(string $id): void
    {
        $this->guardManager('resolver');
        [$errors, $data] = (new ResolveMarketRequest())->validate($_POST);

        if ($errors !== []) {
            Session::set('errors', $errors);
            Session::flash('error', 'Selecione uma opção válida para resolver o mercado.');
            $this->redirectTo($this->marketPath((int) $id));
        }

        try {
            $actorId = Auth::id();
            if ($actorId === null) {
                throw new DomainException('Faça login para resolver mercados.');
            }

            $market = $this->resolutionService->resolveMarket(
                (int) $id,
                (int) $data['resolved_option_id'],
                $actorId,
                $data['resolution_notes']
            );
            Session::flash('success', 'Mercado resolvido com sucesso.');
            $this->redirectTo('/markets/' . $market['slug']);
        } catch (DomainException $exception) {
            Session::set('errors', ['resolved_option_id' => [$exception->getMessage()]]);
            Session::flash('error', $exception->getMessage());
            $this->redirectTo($this->marketPath((int) $id));
        }
    }

    private function guardManager(string $action): void
    {
        if ($this->policy->canManage(Auth::user())) {
            return;
        }

        http_response_code(403);
        Session::flash('error', 'Você não tem permissão para ' . $action . ' mercados.');
        $this->redirectTo('/markets');
    }

    private function marketPath(int $id): string
    {
        $market = $this->markets->getMarketById($id);

        if ($market === null) {
            return '/markets';
        }

        return '/markets/' . $market['slug'];
    }
}
