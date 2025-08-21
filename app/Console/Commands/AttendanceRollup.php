<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\AttendanceRollupJob;

class AttendanceRollup extends Command
{
    protected $signature = 'attendance:rollup {--from=} {--to=}';
    protected $description = 'Build daily attendance from raw logs';

    public function handle()
    {
        $from = $this->option('from') ?? 'yesterday';
        $to   = $this->option('to')   ?? 'yesterday';
        AttendanceRollupJob::dispatch($from, $to);
        $this->info("Rollup job dispatched ($from → $to)");
        return self::SUCCESS;
    }
}
