<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Purge RGPD : comptes sans connexion depuis 3 ans. Suppose un cron hote
// qui lance `schedule:run` chaque minute (voir README.md § 5).
Schedule::command('comptes:purger-inactifs')->daily();
