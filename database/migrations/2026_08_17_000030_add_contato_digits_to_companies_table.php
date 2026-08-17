<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('contato_digits', 32)->nullable()->after('contato');
            $table->index('contato_digits');
        });

        $companies = DB::table('companies')->select('id', 'contato')->get();
        foreach ($companies as $company) {
            $digits = preg_replace('/\D+/', '', (string) $company->contato);
            if ($digits === '') {
                continue;
            }
            if (str_starts_with($digits, '55') && strlen($digits) >= 12) {
                $digits = substr($digits, 2);
            }
            DB::table('companies')->where('id', $company->id)->update([
                'contato_digits' => $digits,
            ]);
        }

        // Índice único parcial: só bloqueia quando há número
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('CREATE UNIQUE INDEX companies_contato_digits_unique ON companies (contato_digits) WHERE contato_digits IS NOT NULL AND contato_digits <> \'\'');
        } else {
            Schema::table('companies', function (Blueprint $table) {
                $table->unique('contato_digits');
            });
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS companies_contato_digits_unique');
        } else {
            Schema::table('companies', function (Blueprint $table) {
                $table->dropUnique(['contato_digits']);
            });
        }

        Schema::table('companies', function (Blueprint $table) {
            $table->dropIndex(['contato_digits']);
            $table->dropColumn('contato_digits');
        });
    }
};
