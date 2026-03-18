<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\HomeController;

$app->get('/', [HomeController::class, 'index']);
$app->get('/register', [AuthController::class, 'showRegister'], ['guest']);
$app->post('/register', [AuthController::class, 'register'], ['guest']);
$app->get('/login', [AuthController::class, 'showLogin'], ['guest']);
$app->post('/login', [AuthController::class, 'login'], ['guest']);
$app->post('/logout', [AuthController::class, 'logout'], ['auth']);
$app->get('/dashboard', [AuthController::class, 'dashboard'], ['auth']);
$app->get('/profile/edit', [AuthController::class, 'editProfile'], ['auth']);
$app->post('/profile/edit', [AuthController::class, 'updateProfile'], ['auth']);
