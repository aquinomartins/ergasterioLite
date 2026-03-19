<?php

declare(strict_types=1);

use App\Controllers\ArtistController;
use App\Controllers\ArtworkController;
use App\Controllers\AuthController;
use App\Controllers\HomeController;
use App\Controllers\MediaController;

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
$app->get('/media/artworks/{file}', [MediaController::class, 'artwork']);
