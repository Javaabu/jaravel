<?php

use Illuminate\Support\Facades\Route;

Route::get('admin-test', function () {
    return response()->json('It works');
});
