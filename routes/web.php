<?php

declare(strict_types=1);

use App\Controllers\ArtistController;
use App\Controllers\ArtworkController;
use App\Controllers\AuthController;
use App\Controllers\HomeController;
use App\Controllers\MarketController;
use App\Controllers\PositionController;
use App\Controllers\RankingController;
use App\Controllers\LiquidityAdminController;
use App\Controllers\LiquidityApiController;
use App\Controllers\LiquidityTeamController;

$app->get('/', [HomeController::class, 'index']);
$app->get('/register', [AuthController::class, 'showRegister'], ['guest']);
$app->post('/register', [AuthController::class, 'register'], ['guest']);
$app->get('/login', [AuthController::class, 'showLogin'], ['guest']);
$app->post('/login', [AuthController::class, 'login'], ['guest']);
$app->post('/logout', [AuthController::class, 'logout'], ['auth']);
$app->get('/dashboard', [AuthController::class, 'dashboard'], ['auth']);
$app->get('/profile/edit', [AuthController::class, 'editProfile'], ['auth']);
$app->post('/profile/edit', [AuthController::class, 'updateProfile'], ['auth']);

$app->get('/artists', [ArtistController::class, 'index']);
$app->get('/artists/create', [ArtistController::class, 'create'], ['auth']);
$app->post('/artists', [ArtistController::class, 'store'], ['auth']);
$app->get('/artists/{slug}', [ArtistController::class, 'show']);

$app->get('/artworks', [ArtworkController::class, 'index']);
$app->get('/artworks/create', [ArtworkController::class, 'create'], ['auth']);
$app->post('/artworks', [ArtworkController::class, 'store'], ['auth']);
$app->get('/artworks/{slug}', [ArtworkController::class, 'show']);

$app->get('/markets', [MarketController::class, 'index']);
$app->get('/markets/create', [MarketController::class, 'create'], ['auth']);
$app->post('/markets', [MarketController::class, 'store'], ['auth']);
$app->get('/markets/{slug}', [MarketController::class, 'show']);
$app->post('/markets/{id}/publish', [MarketController::class, 'publish'], ['auth']);
$app->post('/markets/{id}/close', [MarketController::class, 'close'], ['auth']);
$app->post('/markets/{id}/resolve', [MarketController::class, 'resolve'], ['auth']);

$app->post('/markets/{id}/positions', [PositionController::class, 'store'], ['auth']);

$app->get('/rankings', [RankingController::class, 'index']);

$app->get('/liquidity', [LiquidityAdminController::class, 'index']);
$app->get('/liquidity/create', [LiquidityAdminController::class, 'create']);
$app->post('/liquidity', [LiquidityAdminController::class, 'store']);
$app->get('/liquidity/{id}', [LiquidityAdminController::class, 'show']);
$app->get('/liquidity/{id}/teams', [LiquidityAdminController::class, 'teams']);
$app->post('/liquidity/{id}/teams', [LiquidityAdminController::class, 'createTeam']);
$app->post('/liquidity/{id}/advance-round', [LiquidityAdminController::class, 'advanceRound']);
$app->post('/liquidity/{id}/close', [LiquidityAdminController::class, 'closeSession']);
$app->get('/liquidity/{id}/projector', [LiquidityAdminController::class, 'projector']);

$app->get('/liquidity/team/login', [LiquidityTeamController::class, 'loginForm']);
$app->post('/liquidity/team/login', [LiquidityTeamController::class, 'login']);
$app->get('/liquidity/team/dashboard', [LiquidityTeamController::class, 'dashboard']);
$app->post('/liquidity/team/action', [LiquidityTeamController::class, 'submitAction']);

$app->get('/api/liquidity/{id}/session', [LiquidityApiController::class, 'getSession']);
$app->get('/api/liquidity/{id}/ranking', [LiquidityApiController::class, 'getRanking']);
$app->get('/api/liquidity/{id}/feed', [LiquidityApiController::class, 'getFeed']);
$app->get('/api/liquidity/{id}/pool', [LiquidityApiController::class, 'getPoolState']);
$app->get('/api/liquidity/{id}/projector-state', [LiquidityApiController::class, 'getProjectorState']);
$app->get('/api/liquidity/team/state', [LiquidityApiController::class, 'getTeamState']);
