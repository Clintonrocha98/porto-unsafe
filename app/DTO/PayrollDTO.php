<?php

declare(strict_types=1);

namespace App\DTO;

use Carbon\Carbon;

class PayrollDTO
{
    public function __construct(
        public ?string $registration,
        public ?string $name,
        public ?string $role,
        public ?Carbon $admission_date,
        public ?Carbon $resignation_date,
        public ?string $employment_regime,
        public ?string $workplace,
        public ?int $workload_hours,
        public ?float $base_salary,
        public ?float $allowances,
        public ?float $deductions,
        public ?float $taxes,
        public ?float $net_salary,
        public int $month,
        public int $year,
    ) {}

    public static function make(array $data): self
    {
        return new self(
            registration: $data['registration'] ?? null,
            name: $data['name'] ?? null,
            role: $data['role'] ?? null,
            admission_date: $data['admission_date'] ?? null,
            resignation_date: $data['resignation_date'] ?? null,
            employment_regime: $data['employment_regime'] ?? null,
            workplace: $data['workplace'] ?? null,
            workload_hours: $data['workload_hours'] ?? null,
            base_salary: $data['base_salary'] ?? null,
            allowances: $data['allowances'] ?? null,
            deductions: $data['deductions'] ?? null,
            taxes: $data['taxes'] ?? null,
            net_salary: $data['net_salary'] ?? null,
            month: $data['month'],
            year: $data['year'],
        );
    }

    public function toArray(): array
    {
        return [
            'registration' => $this->registration,
            'name' => $this->name,
            'role' => $this->role,
            'admission_date' => $this->admission_date?->format('Y-m-d'),
            'resignation_date' => $this->resignation_date?->format('Y-m-d'),
            'employment_regime' => $this->employment_regime,
            'workplace' => $this->workplace,
            'workload_hours' => $this->workload_hours,
            'base_salary' => $this->base_salary,
            'allowances' => $this->allowances,
            'deductions' => $this->deductions,
            'taxes' => $this->taxes,
            'net_salary' => $this->net_salary,
            'month' => $this->month,
            'year' => $this->year,
        ];
    }
}
