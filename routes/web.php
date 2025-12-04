<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\KioskController;
use App\Http\Controllers\MonitorController;
use App\Http\Controllers\CounterController;

// Landing can redirect to kiosk
Route::get('/', [KioskController::class, 'index'])->name('home');

// Kiosk flow
Route::get('/kiosk', [KioskController::class, 'index'])->name('kiosk.index');
Route::post('/kiosk/service', [KioskController::class, 'chooseService'])->name('kiosk.service');
Route::post('/kiosk/priority', [KioskController::class, 'choosePriority'])->name('kiosk.priority');
Route::post('/kiosk/issue', [KioskController::class, 'issueTicket'])->name('kiosk.issue');
Route::get('/kiosk/ticket/{ticket}', [KioskController::class, 'showTicket'])->name('kiosk.ticket');

// Monitor (TV)
Route::get('/monitor', [MonitorController::class, 'index'])->name('monitor.index');

// Counter (Registrar/Cashier)
Route::get('/counter', [CounterController::class, 'select'])->name('counter.select');
Route::post('/counter/claim', [CounterController::class, 'claim'])->name('counter.claim');
Route::post('/counter/release', [CounterController::class, 'release'])->name('counter.release');
Route::get('/counter/{counter}', [CounterController::class, 'show'])->name('counter.show');

// Counter actions
Route::post('/counter/{counter}/next', [CounterController::class, 'next'])->name('counter.next');
Route::post('/counter/{counter}/hold/{ticket}', [CounterController::class, 'hold'])->name('counter.hold');
Route::post('/counter/{counter}/call-again/{ticket}', [CounterController::class, 'callAgain'])->name('counter.callAgain');
Route::delete('/counter/{counter}/hold/{ticket}', [CounterController::class, 'removeHold'])->name('counter.removeHold');

