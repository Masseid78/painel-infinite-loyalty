<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function show(): JsonResponse
    {
        $settings = Setting::current();

        return response()->json($settings);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'meta_mensal' => ['required', 'numeric', 'min:0'],
            'meta_contatos_semana' => ['required', 'integer', 'min:0'],
            'valor_plano_fidelidade' => ['nullable', 'numeric', 'min:0'],
            'valor_plano_completo' => ['nullable', 'numeric', 'min:0'],
        ]);

        $settings = Setting::current();
        $settings->fill([
            'meta_mensal' => $data['meta_mensal'],
            'meta_contatos_semana' => $data['meta_contatos_semana'],
            'valor_plano_fidelidade' => $data['valor_plano_fidelidade'] ?? $settings->valor_plano_fidelidade,
            'valor_plano_completo' => $data['valor_plano_completo'] ?? $settings->valor_plano_completo,
        ]);
        $settings->save();

        return response()->json([
            'message' => 'Meta atualizada com sucesso.',
            'settings' => $settings,
        ]);
    }
}
