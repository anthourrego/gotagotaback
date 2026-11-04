<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prestamos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes');
            $table->foreignId('cobrador_id')->constrained('users');
            $table->decimal('monto', 14, 2);
            $table->decimal('tasa_interes', 6, 2); // % total sobre el capital
            $table->decimal('total_a_pagar', 14, 2);
            $table->decimal('saldo', 14, 2);
            $table->unsignedInteger('numero_cuotas');
            $table->decimal('valor_cuota', 14, 2);
            $table->string('frecuencia', 20); // diario, semanal, quincenal, mensual
            $table->boolean('incluye_domingos')->default(false);
            $table->date('fecha_desembolso');
            $table->date('fecha_primera_cuota');
            $table->date('fecha_vencimiento');
            $table->string('estado', 20)->default('activo'); // activo, pagado, cancelado
            $table->text('notas')->nullable();
            $table->timestamps();

            $table->index(['estado', 'cobrador_id']);
        });

        Schema::create('cuotas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prestamo_id')->constrained('prestamos')->cascadeOnDelete();
            $table->unsignedInteger('numero');
            $table->date('fecha_vencimiento');
            $table->decimal('valor', 14, 2);
            $table->decimal('valor_pagado', 14, 2)->default(0);
            $table->string('estado', 20)->default('pendiente'); // pendiente, parcial, pagada
            $table->timestamps();

            $table->unique(['prestamo_id', 'numero']);
            $table->index(['estado', 'fecha_vencimiento']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cuotas');
        Schema::dropIfExists('prestamos');
    }
};
