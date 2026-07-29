<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('model:prune')->daily();
Schedule::command('billing:generate')->monthlyOn(1, '01:00')->withoutOverlapping();
