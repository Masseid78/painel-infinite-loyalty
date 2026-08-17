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
        'contato_digits',
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

    protected static function booted(): void
    {
        static::saving(function (Company $company) {
            $company->contato_digits = static::normalizeContato($company->contato);
        });
    }

    public static function normalizeContato(?string $contato): ?string
    {
        if ($contato === null || trim($contato) === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $contato) ?: '';

        if (strlen($digits) < 8) {
            return null;
        }

        if (str_starts_with($digits, '55') && strlen($digits) >= 12) {
            $digits = substr($digits, 2);
        }

        return $digits;
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
