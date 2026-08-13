<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupController extends Controller
{
    public function download(): StreamedResponse
    {
        $payload = [
            'exported_at' => now()->toIso8601String(),
            'settings' => Setting::current()->toArray(),
            'companies' => Company::query()->orderBy('id')->get()->toArray(),
        ];

        $filename = 'backup-infinite-loyalty-'.now()->format('Y-m-d-His').'.json';

        return response()->streamDownload(function () use ($payload) {
            echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }, $filename, [
            'Content-Type' => 'application/json',
        ]);
    }

    public function restore(Request $request): JsonResponse
    {
        $data = $request->validate([
            'settings' => ['nullable', 'array'],
            'companies' => ['required', 'array'],
            'companies.*.nome' => ['required', 'string'],
            'companies.*.status' => ['nullable', 'string'],
            'companies.*.plano' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($data) {
            if (! empty($data['settings'])) {
                $settings = Setting::current();
                $settings->fill(collect($data['settings'])->only([
                    'meta_mensal',
                    'meta_contatos_semana',
                    'valor_plano_fidelidade',
                    'valor_plano_completo',
                ])->toArray());
                $settings->save();
            }

            Company::query()->delete();

            foreach ($data['companies'] as $row) {
                Company::create([
                    'nome' => $row['nome'],
                    'contato' => $row['contato'] ?? null,
                    'nicho' => $row['nicho'] ?? null,
                    'status' => $row['status'] ?? 'novo_contato',
                    'plano' => $row['plano'] ?? 'nenhum',
                    'data_contato' => $row['data_contato'] ?? null,
                    'proximo_retorno' => $row['proximo_retorno'] ?? null,
                    'observacao' => $row['observacao'] ?? null,
                ]);
            }
        });

        return response()->json(['message' => 'Backup restaurado com sucesso.']);
    }
}
