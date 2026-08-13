<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    public const STATUSES = [
        'novo_contato' => 'Novo contato',
        'respondeu' => 'Respondeu',
        'demonstracao' => 'Demonstração',
        'retorno' => 'Retorno',
        'assinou' => 'Assinou',
        'nao_interessado' => 'Não interessado',
    ];

    public const PLANOS = [
        'nenhum' => 'Nenhum',
        'fidelidade' => 'Fidelidade',
        'completo' => 'Completo',
    ];

    protected $fillable = [
        'nome',
        'contato',
        'nicho',
        'status',
        'plano',
        'data_contato',
        'proximo_retorno',
        'observacao',
    ];

    protected function casts(): array
    {
        return [
            'data_contato' => 'date',
            'proximo_retorno' => 'date',
        ];
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function planoLabel(): string
    {
        return self::PLANOS[$this->plano] ?? $this->plano;
    }

    public function monthlyValue(Setting $settings): float
    {
        return match ($this->plano) {
            'fidelidade' => (float) $settings->valor_plano_fidelidade,
            'completo' => (float) $settings->valor_plano_completo,
            default => 0.0,
        };
    }
}
