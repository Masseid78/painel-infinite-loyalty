<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->decimal('meta_mensal', 12, 2)->default(3000);
            $table->unsignedInteger('meta_contatos_semana')->default(120);
            $table->decimal('valor_plano_fidelidade', 12, 2)->default(97);
            $table->decimal('valor_plano_completo', 12, 2)->default(197);
            $table->timestamps();
        });

        DB::table('settings')->insert([
            'meta_mensal' => 3000,
            'meta_contatos_semana' => 120,
            'valor_plano_fidelidade' => 97,
            'valor_plano_completo' => 197,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
