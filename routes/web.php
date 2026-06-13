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
use App\Controllers\LiquidityPredictionController;
use App\Controllers\LiquidityGameController;

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

$app->get('/liquidity', [LiquidityAdminController::class, 'index'], ['auth']);
$app->get('/liquidity/create', [LiquidityGameController::class, 'create'], ['auth']);
$app->post('/liquidity', [LiquidityGameController::class, 'store'], ['auth']);
$app->get('/liquidity/my-games', [LiquidityGameController::class, 'myGames'], ['auth']);
$app->get('/liquidity/join', [LiquidityGameController::class, 'joinForm'], ['auth']);
$app->post('/liquidity/join', [LiquidityGameController::class, 'join'], ['auth']);
$app->get('/liquidity/games/{gameId}/teacher', [LiquidityGameController::class, 'teacherPanel'], ['auth']);
$app->post('/liquidity/games/{gameId}/participants/{participantId}/approve', [LiquidityGameController::class, 'approve'], ['auth']);
$app->post('/liquidity/games/{gameId}/participants/{participantId}/reject', [LiquidityGameController::class, 'reject'], ['auth']);
$app->get('/liquidity/games/{gameId}/my-team', [LiquidityGameController::class, 'myTeam'], ['auth']);
$app->post('/liquidity/games/{gameId}/my-team/action', [LiquidityGameController::class, 'submitMyTeamAction'], ['auth']);
$app->post('/liquidity/games/{gameId}/advance-round', [LiquidityGameController::class, 'advanceGameRound'], ['auth']);
$app->get('/liquidity/games/{gameId}/arena', [LiquidityGameController::class, 'arena']);
$app->get('/liquidity/{id}', [LiquidityAdminController::class, 'show'], ['auth']);
$app->get('/liquidity/{id}/teams', [LiquidityAdminController::class, 'teams'], ['auth']);
$app->post('/liquidity/{id}/teams', [LiquidityAdminController::class, 'createTeam'], ['auth']);
$app->post('/liquidity/{id}/actions', [LiquidityAdminController::class, 'registerTeamAction'], ['auth']);
$app->post('/liquidity/{id}/advance-round', [LiquidityAdminController::class, 'advanceRound'], ['auth']);
$app->post('/liquidity/{id}/evaluate-semifinal', [LiquidityAdminController::class, 'evaluateSemifinal'], ['auth']);
$app->post('/liquidity/{id}/close-final', [LiquidityAdminController::class, 'closeFinal'], ['auth']);
$app->post('/liquidity/{id}/close', [LiquidityAdminController::class, 'closeSession'], ['auth']);
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


$app->get('/liquidity/{sessionId}/predictions/create', [LiquidityPredictionController::class, 'createForm']);
$app->post('/liquidity/{sessionId}/predictions', [LiquidityPredictionController::class, 'store']);
$app->get('/liquidity/predictions/{marketId}', [LiquidityPredictionController::class, 'show']);
$app->post('/liquidity/predictions/{marketId}/close', [LiquidityPredictionController::class, 'close']);
$app->post('/liquidity/predictions/{marketId}/resolve', [LiquidityPredictionController::class, 'resolve']);
$app->post('/liquidity/predictions/{marketId}/bets', [LiquidityPredictionController::class, 'placeBet']);
$app->get('/api/liquidity/{sessionId}/predictions', [LiquidityPredictionController::class, 'listBySession']);
$app->get('/api/liquidity/predictions/{marketId}', [LiquidityPredictionController::class, 'getMarket']);
