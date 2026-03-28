<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum Entity: string implements HasLabel
{
    case Administracao = 'pm_portoseguro';
    case Educacao = 'educ_portoseguro';
    case Saude = 'saude_portoseguro';

    public function getLabel(): ?string
    {
        return match ($this) {
            Entity::Administracao => 'Administração',
            Entity::Educacao => 'Educação',
            Entity::Saude => 'Saúde',
        };
    }
}
