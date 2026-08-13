<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('contato')->nullable();
            $table->string('nicho')->nullable();
            $table->string('status')->default('novo_contato');
            $table->string('plano')->default('nenhum');
            $table->date('data_contato')->nullable();
            $table->date('proximo_retorno')->nullable();
            $table->text('observacao')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('plano');
            $table->index('data_contato');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
