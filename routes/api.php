<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\InitialBalanceController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\TransactionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('auth')->group(function () {
    /**
     * Credentials_auth
     */
    Route::post('sign-up', [AuthController::class, 'signUp']);
    Route::post('sign-in', [AuthController::class, 'signIn']);

    /**
     * Social_auth
     */
    Route::get('google/redirect', [AuthController::class, 'googleRedirect']);
    Route::get('google/callback', [AuthController::class, 'googleCallback']);

    Route::middleware('jwt')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
    });
});

Route::prefix('organization')->group(function () {
    Route::middleware('jwt')->group(function () {
        Route::get('/', [OrganizationController::class, 'getOrganizationBranches']);
        Route::post('create-organization', [OrganizationController::class, 'createOrganization']);
        Route::patch('{id}/update-organization', [OrganizationController::class, 'updateOrganization']);
    });
});

Route::prefix('branch')->group(function () {
    Route::middleware('jwt')->group(function () {
        Route::post('create-branch', [BranchController::class, 'createBranch']);
        Route::patch('{id}/update-branch', [BranchController::class, 'updateBranch']);
    });
});

Route::prefix('initial-balance')->group(function () {
    Route::middleware('jwt')->group(function () {
        Route::post('create-initial-balance', [InitialBalanceController::class, 'createInitialBalance']);
        Route::patch('{id}/update-initial-balance', [InitialBalanceController::class, 'updateInitialBalance']);
    });
});

Route::prefix('transaction')->group(function () {
    // Route::middleware('jwt')->group(function () {
        Route::get('/', [TransactionController::class, 'getTransactions']);
        Route::post('create-transaction', [TransactionController::class, 'createTransaction']);
    // });
});
