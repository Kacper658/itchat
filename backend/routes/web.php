<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json(['app' => 'ITChat API', 'status' => 'ok']);
});

// CORS preflight / health już pod /up (Laravel)
