<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('documento', 30)->unique();
            $table->string('telefono', 30)->nullable();
            $table->string('direccion')->nullable();
            $table->string('barrio')->nullable();
            $table->string('referencia_nombre')->nullable();
            $table->string('referencia_telefono', 30)->nullable();
            $table->foreignId('ruta_id')->nullable()->constrained('rutas')->nullOnDelete();
            $table->text('notas')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
