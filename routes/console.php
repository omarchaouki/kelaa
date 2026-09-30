<?php

use App\Jobs\AggregateContactEvents;
use App\Models\ContactEvent;
use App\Models\OtpCode;
use Illuminate\Support\Facades\Schedule;

// Statistics for owners: yesterday's contact events, rolled up after midnight (Casablanca time).
Schedule::job(AggregateContactEvents::forYesterday())->dailyAt('00:20')->onOneServer();

// Data minimization: expired OTP codes and old raw contact events are deleted.
Schedule::command('model:prune', ['--model' => [OtpCode::class, ContactEvent::class]])->dailyAt('03:10')->onOneServer();

// Safety net: scores are recomputed on every review change, and fully each night.
Schedule::command('ranking:recalculate')->dailyAt('02:30')->onOneServer();

Schedule::command('horizon:snapshot')->everyFiveMinutes();
