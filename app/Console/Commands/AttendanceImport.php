<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ZKTecoImporter;
use App\Jobs\AttendanceRollupJob;
use Carbon\Carbon;

class AttendanceImport extends Command
{
    protected $signature = 'attendance:import {--users=} {--logs=}';
    protected $description = 'Import ZKTeco users & logs from JSON files';

    public function handle()
    {
        $importer = app(ZKTecoImporter::class);
        $usersPath = $this->option('users');
        $logsPath  = $this->option('logs');

        if (!$usersPath || !$logsPath) {
            $this->error('Provide --users= and --logs=');
            return self::FAILURE;
        }

        [$uc, $lc] = $importer->importFromStorage($usersPath, $logsPath);
        $this->info("Users synced: $uc, Logs imported: $lc");

        $from = Carbon::now('Asia/Dhaka')->subDays(7)->startOfDay()->toDateTimeString();
        $to   = Carbon::now('Asia/Dhaka')->endOfDay()->toDateTimeString();
        AttendanceRollupJob::dispatch($from, $to);

        $this->info("Rollup job dispatched ($from → $to)");
        return self::SUCCESS;
    }
}
