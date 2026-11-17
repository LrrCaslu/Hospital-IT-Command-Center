<?php
use Illuminate\Support\Facades\Schedule;
Schedule::command('noc:collect --once')->everyMinute()->withoutOverlapping();
