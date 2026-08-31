<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DeployActionController;
use App\Http\Controllers\DeploymentController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn() => redirect()->route('deployments.index'));

Route::middleware(['guest', 'throttle:auth-actions'])->group(function () {
    Route::get('/login', [LoginController::class, 'index'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.attempt');
});

Route::get('/logout', [LoginController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::middleware(['auth', 'role:super_admin,admin'])->group(function () {
    Route::get('/deployments', [DeploymentController::class, 'index'])->name('deployments.index');
    Route::get('/deployments/{deployment}', [DeploymentController::class, 'show'])->name(
        'deployments.show',
    );
    Route::get('/deployments/{deployment}/history', [DeploymentController::class, 'history'])->name(
        'deployments.history',
    );

    Route::post('/deployments/{deployment}/redeploy', [
        DeployActionController::class,
        'redeploy',
    ])->name('deployments.redeploy');
    Route::post('/deployments/{deployment}/rebase', [
        DeployActionController::class,
        'rebase',
    ])->name('deployments.rebase');
    Route::post('/deployments/{deployment}/env', [
        DeployActionController::class,
        'updateEnv',
    ])->name('deployments.env');
    Route::post('/deployments/{deployment}/env/restore', [
        DeployActionController::class,
        'restoreEnv',
    ])->name('deployments.env.restore');
});

Route::middleware(['auth', 'role:super_admin'])->group(function () {
    Route::get('/deployments-new', [DeploymentController::class, 'create'])->name(
        'deployments.create',
    );
    Route::post('/deployments', [DeploymentController::class, 'store'])->name('deployments.store');
    Route::delete('/deployments/{deployment}', [DeploymentController::class, 'destroy'])->name(
        'deployments.destroy',
    );
    Route::post('/deployments/{deployment}/assign', [DeploymentController::class, 'assign'])->name(
        'deployments.assign',
    );
    Route::post('/deployments/{deployment}/ssh-key', [DeploymentController::class, 'updateSshKey'])->name(
        'deployments.ssh-key',
    );
    Route::post('/deployments/{deployment}/ssh-key/sync', [DeploymentController::class, 'syncSshKey'])->name(
        'deployments.ssh-key.sync',
    );
    Route::post('/deployments/{deployment}/ssh-config/sync', [DeploymentController::class, 'syncSshConfig'])->name(
        'deployments.ssh-config.sync',
    );

    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/new', [UserController::class, 'create'])->name('users.create');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
});
