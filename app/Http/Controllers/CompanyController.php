<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Company::query()->orderByDesc('updated_at');

        if ($search = trim((string) $request->query('q', ''))) {
            $like = "%{$search}%";
            $operator = $query->getConnection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

            $query->where(function ($q) use ($like, $operator) {
                $q->where('nome', $operator, $like)
                    ->orWhere('contato', $operator, $like)
                    ->orWhere('nicho', $operator, $like);
            });
        }

        if ($status = $request->query('status')) {
            if ($status !== 'todos' && $status !== '') {
                $query->where('status', $status);
            }
        }

        $companies = $query->get()->map(fn (Company $c) => $this->transform($c));

        return response()->json([
            'data' => $companies,
            'statuses' => Company::STATUSES,
            'planos' => Company::PLANOS,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);
        $company = Company::create($data);

        return response()->json([
            'message' => 'Empresa cadastrada.',
            'data' => $this->transform($company),
        ], 201);
    }

    public function show(Company $company): JsonResponse
    {
        return response()->json(['data' => $this->transform($company)]);
    }

    public function update(Request $request, Company $company): JsonResponse
    {
        $data = $this->validated($request);
        $company->update($data);

        return response()->json([
            'message' => 'Empresa atualizada.',
            'data' => $this->transform($company->fresh()),
        ]);
    }

    public function destroy(Company $company): JsonResponse
    {
        $company->delete();

        return response()->json(['message' => 'Empresa removida.']);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'nome' => ['required', 'string', 'max:255'],
            'contato' => ['nullable', 'string', 'max:255'],
            'nicho' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'string', 'in:'.implode(',', array_keys(Company::STATUSES))],
            'plano' => ['required', 'string', 'in:'.implode(',', array_keys(Company::PLANOS))],
            'data_contato' => ['nullable', 'date'],
            'proximo_retorno' => ['nullable', 'date'],
            'observacao' => ['nullable', 'string'],
        ]);
    }

    private function transform(Company $company): array
    {
        return [
            'id' => $company->id,
            'nome' => $company->nome,
            'contato' => $company->contato,
            'nicho' => $company->nicho,
            'status' => $company->status,
            'status_label' => $company->statusLabel(),
            'plano' => $company->plano,
            'plano_label' => $company->planoLabel(),
            'data_contato' => optional($company->data_contato)?->format('Y-m-d'),
            'proximo_retorno' => optional($company->proximo_retorno)?->format('Y-m-d'),
            'observacao' => $company->observacao,
            'created_at' => optional($company->created_at)?->toIso8601String(),
            'updated_at' => optional($company->updated_at)?->toIso8601String(),
        ];
    }
}
