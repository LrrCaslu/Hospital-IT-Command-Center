<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
Route::middleware(['web','auth','permission:dashboard.view','throttle:60,1'])->get('/dashboard',[DashboardController::class,'data'])->name('api.dashboard');
