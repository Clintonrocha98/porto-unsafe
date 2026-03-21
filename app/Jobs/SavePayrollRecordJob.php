<?php

declare(strict_types=1);

namespace App\Jobs;

use App\DTO\PayrollDTO;
use App\Models\Payroll;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SavePayrollRecordJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $entity,
        public PayrollDTO $dto
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $data = $this->dto->toArray();
        $data['entity'] = $this->entity;

        $data['base_salary'] = (float) $data['base_salary'];
        $data['allowances'] = (float) $data['allowances'];
        $data['deductions'] = (float) $data['deductions'];
        $data['taxes'] = (float) $data['taxes'];
        $data['net_salary'] = (float) $data['net_salary'];

        Payroll::query()->upsert([$data], ['entity', 'registration', 'role', 'month', 'year'], [
            'name', 'admission_date', 'resignation_date',
            'employment_regime', 'workplace', 'workload_hours',
            'base_salary', 'allowances', 'deductions', 'taxes', 'net_salary',
        ]);
    }
}
