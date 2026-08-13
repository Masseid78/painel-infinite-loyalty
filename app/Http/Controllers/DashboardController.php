<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $settings = Setting::current();
        $weekStart = Carbon::now()->startOfWeek(Carbon::MONDAY)->startOfDay();
        $weekEnd = Carbon::now()->endOfWeek(Carbon::SUNDAY)->endOfDay();
        $monthStart = Carbon::now()->startOfMonth()->startOfDay();
        $monthEnd = Carbon::now()->endOfMonth()->endOfDay();

        $contatosSemana = Company::query()
            ->whereBetween('data_contato', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->count();

        $empresas = Company::query()->count();
        $responderam = Company::query()
            ->whereIn('status', ['respondeu', 'demonstracao', 'retorno', 'assinou'])
            ->count();

        $assinaturas = Company::query()->where('status', 'assinou')->count();
        $conversao = $empresas > 0 ? round(($assinaturas / $empresas) * 100, 1) : 0.0;

        $signed = Company::query()
            ->where('status', 'assinou')
            ->get(['plano']);

        $receitaRecorrente = $signed->sum(fn (Company $c) => $c->monthlyValue($settings));

        $receitaMes = Company::query()
            ->where('status', 'assinou')
            ->whereBetween('data_contato', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->get(['plano'])
            ->sum(fn (Company $c) => $c->monthlyValue($settings));

        // Meta mensal acompanha a receita recorrente atual (assinaturas ativas)
        $atualMeta = (float) $receitaRecorrente;
        $metaMensal = (float) $settings->meta_mensal;
        $faltam = max(0, $metaMensal - $atualMeta);
        $percentual = $metaMensal > 0 ? round(($atualMeta / $metaMensal) * 100, 1) : 0.0;

        $metaContatos = (int) $settings->meta_contatos_semana;
        $restantesContatos = max(0, $metaContatos - $contatosSemana);

        return response()->json([
            'meta' => [
                'atual' => $atualMeta,
                'meta_mensal' => $metaMensal,
                'faltam' => $faltam,
                'percentual' => $percentual,
                'meta_contatos_semana' => $metaContatos,
            ],
            'cards' => [
                'contatos_semana' => [
                    'atual' => $contatosSemana,
                    'meta' => $metaContatos,
                    'restantes' => $restantesContatos,
                ],
                'empresas' => [
                    'total' => $empresas,
                    'responderam' => $responderam,
                ],
                'assinaturas' => [
                    'total' => $assinaturas,
                    'conversao' => $conversao,
                ],
                'receita_recorrente' => [
                    'valor' => $receitaRecorrente,
                    'mes_atual' => $receitaMes,
                ],
            ],
            'settings' => [
                'meta_mensal' => $metaMensal,
                'meta_contatos_semana' => $metaContatos,
                'valor_plano_fidelidade' => (float) $settings->valor_plano_fidelidade,
                'valor_plano_completo' => (float) $settings->valor_plano_completo,
            ],
        ]);
    }
}
