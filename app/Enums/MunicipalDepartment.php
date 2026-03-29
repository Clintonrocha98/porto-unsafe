<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum MunicipalDepartment: string implements HasLabel
{
    case Administracao = 'pm_portoseguro';
    case Educacao = 'educ_portoseguro';
    case Saude = 'saude_portoseguro';

    public function getLabel(): ?string
    {
        return match ($this) {
            MunicipalDepartment::Administracao => 'Administração',
            MunicipalDepartment::Educacao => 'Educação',
            MunicipalDepartment::Saude => 'Saúde',
        };
    }
}
