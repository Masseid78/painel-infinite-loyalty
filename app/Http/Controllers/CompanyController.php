<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CompanyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Company::query()
            ->orderByDesc('data_contato')
            ->orderByDesc('id');

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

        $periodo = (string) $request->query('periodo', 'hoje');
        $today = Carbon::now(config('app.timezone', 'America/Sao_Paulo'))->toDateString();
        $weekStart = Carbon::now(config('app.timezone', 'America/Sao_Paulo'))->startOfWeek(Carbon::MONDAY)->toDateString();

        if ($periodo === 'hoje') {
            $query->whereDate('data_contato', $today);
        } elseif ($periodo === 'semana') {
            $query->whereDate('data_contato', '>=', $weekStart)
                ->whereDate('data_contato', '<=', $today);
        } elseif ($periodo === 'anteriores') {
            $query->where(function ($q) use ($today) {
                $q->whereDate('data_contato', '<', $today)
                    ->orWhereNull('data_contato');
            });
        }

        $companies = $query->get()->map(fn (Company $c) => $this->transform($c));

        $counts = [
            'hoje' => Company::query()->whereDate('data_contato', $today)->count(),
            'semana' => Company::query()
                ->whereDate('data_contato', '>=', $weekStart)
                ->whereDate('data_contato', '<=', $today)
                ->count(),
            'anteriores' => Company::query()
                ->where(function ($q) use ($today) {
                    $q->whereDate('data_contato', '<', $today)
                        ->orWhereNull('data_contato');
                })
                ->count(),
            'todos' => Company::query()->count(),
        ];

        return response()->json([
            'data' => $companies,
            'counts' => $counts,
            'periodo' => $periodo,
            'statuses' => Company::STATUSES,
            'planos' => Company::PLANOS,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);
        $this->assertUniqueContato($data['contato'] ?? null);

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
        $this->assertUniqueContato($data['contato'] ?? null, $company->id);

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

    private function assertUniqueContato(?string $contato, ?int $ignoreId = null): void
    {
        $digits = Company::normalizeContato($contato);
        if ($digits === null) {
            return;
        }

        $query = Company::query()->where('contato_digits', $digits);
        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'contato' => 'Esse número/WhatsApp já está cadastrado. Não dá pra repetir contato.',
            ]);
        }
    }

    private function transform(Company $company): array
    {
        return [
            'id' => $company->id,
            'nome' => $company->nome,
            'contato' => $company->contato,
            'contato_digits' => $company->contato_digits,
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
