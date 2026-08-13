<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'meta_mensal',
        'meta_contatos_semana',
        'valor_plano_fidelidade',
        'valor_plano_completo',
    ];

    protected function casts(): array
    {
        return [
            'meta_mensal' => 'decimal:2',
            'meta_contatos_semana' => 'integer',
            'valor_plano_fidelidade' => 'decimal:2',
            'valor_plano_completo' => 'decimal:2',
        ];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'meta_mensal' => 3000,
            'meta_contatos_semana' => 120,
            'valor_plano_fidelidade' => 97,
            'valor_plano_completo' => 197,
        ]);
    }
}
