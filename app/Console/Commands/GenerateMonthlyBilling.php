<?php

namespace App\Console\Commands;

use App\Services\BillingService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class GenerateMonthlyBilling extends Command
{
    protected $signature = 'billing:generate {period? : Billing period in YYYY-MM format} {--due= : Due date in YYYY-MM-DD format} {--group= : Optional group ID}';

    protected $description = 'Generate monthly tuition charges for active enrollments';

    public function handle(BillingService $billing): int
    {
        $period = $this->argument('period') ?: now()->format('Y-m');
        if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $period)) {
            $this->error('Period must use YYYY-MM format.');
            return self::FAILURE;
        }

        $dueDate = $this->option('due') ?: CarbonImmutable::createFromFormat('Y-m-d', $period.'-10')->toDateString();
        $groupId = $this->option('group') ? (int) $this->option('group') : null;

        $result = $billing->generate($period, $dueDate, $groupId);
        $this->info("Created: {$result['created']}; skipped existing: {$result['skipped']}.");

        return self::SUCCESS;
    }
}
