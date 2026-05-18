<?php

use App\Http\Controllers\Api\V1\ContactController;
use App\Http\Controllers\Api\V1\DocsController;
use App\Http\Controllers\Api\V1\InvoiceController;
use App\Http\Controllers\Api\V1\InvoicePaymentController;
use App\Http\Controllers\Api\V1\ProductController;
use Illuminate\Support\Facades\Route;

Route::middleware('internal.auth')->prefix('v1')->group(function (): void {
    Route::get('/contacts', [ContactController::class, 'index']);
    Route::get('/contacts/{id}', [ContactController::class, 'show']);
    Route::post('/contacts', [ContactController::class, 'store']);
    Route::put('/contacts/{id}', [ContactController::class, 'update']);

    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/{id}', [ProductController::class, 'show']);
    Route::post('/products', [ProductController::class, 'store']);
    Route::put('/products/{id}', [ProductController::class, 'update']);
    Route::get('/products/{id}/stock', [ProductController::class, 'stock']);

    Route::get('/invoices', [InvoiceController::class, 'index']);
    Route::get('/invoices/{id}', [InvoiceController::class, 'show']);
    Route::post('/invoices', [InvoiceController::class, 'store']);
    Route::get('/invoices/{id}/status', [InvoiceController::class, 'status']);

    Route::get('/invoices/{invoiceId}/payments', [InvoicePaymentController::class, 'index']);
    Route::post('/invoices/{invoiceId}/payments', [InvoicePaymentController::class, 'store']);
});

Route::get('/docs/openapi.yaml', [DocsController::class, 'openapi']);
Route::get('/docs', [DocsController::class, 'swagger']);
