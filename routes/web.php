<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'app' => 'Infinite Loyalty API',
        'status' => 'ok',
    ]);
});
