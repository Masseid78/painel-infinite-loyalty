<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('companies', 'contato_digits')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->string('contato_digits', 32)->nullable()->after('contato');
                $table->index('contato_digits');
            });
        }

        $companies = DB::table('companies')->select('id', 'contato', 'observacao')->orderBy('id')->get();

        foreach ($companies as $company) {
            $digits = preg_replace('/\D+/', '', (string) $company->contato) ?: '';

            if ($digits === '') {
                DB::table('companies')->where('id', $company->id)->update(['contato_digits' => null]);
                continue;
            }

            if (str_starts_with($digits, '55') && strlen($digits) >= 12) {
                $digits = substr($digits, 2);
            }

            DB::table('companies')->where('id', $company->id)->update([
                'contato_digits' => $digits,
            ]);
        }

        // Mantém o mais antigo; limpa o dígito dos repetidos pra o índice único passar
        $duplicates = DB::table('companies')
            ->select('contato_digits', DB::raw('MIN(id) as keep_id'))
            ->whereNotNull('contato_digits')
            ->where('contato_digits', '<>', '')
            ->groupBy('contato_digits')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $dup) {
            $extras = DB::table('companies')
                ->where('contato_digits', $dup->contato_digits)
                ->where('id', '!=', $dup->keep_id)
                ->get(['id', 'observacao']);

            foreach ($extras as $extra) {
                $note = '[AVISO] Número repetido — mantido só no cadastro #'.$dup->keep_id;
                $obs = trim((string) $extra->observacao);
                $obs = $obs === '' ? $note : $obs."\n".$note;

                DB::table('companies')->where('id', $extra->id)->update([
                    'contato_digits' => null,
                    'observacao' => $obs,
                ]);
            }
        }

        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS companies_contato_digits_unique');
            DB::statement('CREATE UNIQUE INDEX companies_contato_digits_unique ON companies (contato_digits) WHERE contato_digits IS NOT NULL AND contato_digits <> \'\'');
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS companies_contato_digits_unique');
        }

        if (Schema::hasColumn('companies', 'contato_digits')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->dropColumn('contato_digits');
            });
        }
    }
};
