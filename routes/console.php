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

// Unpaid orders, plan and placement expiry, reminders 7 days and 1 day before.
Schedule::command('billing:maintain')->hourly()->onOneServer();

// Personal data exports are downloadable for 7 days only.
Schedule::command('privacy:prune-exports')->dailyAt('03:30')->onOneServer();

// Split sitemaps for search engines.
Schedule::command('sitemap:generate')->dailyAt('04:00')->onOneServer();

Schedule::command('horizon:snapshot')->everyFiveMinutes();
