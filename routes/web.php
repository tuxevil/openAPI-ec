<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'name' => 'openapi-ec',
        'docs' => url('/api/docs'),
        'health' => url('/up'),
    ]);
});
