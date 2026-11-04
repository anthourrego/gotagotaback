<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prestamo_id')->constrained('prestamos');
            $table->foreignId('user_id')->constrained('users');
            $table->decimal('monto', 14, 2);
            $table->date('fecha');
            $table->string('metodo', 20)->default('efectivo'); // efectivo, transferencia
            $table->text('nota')->nullable();
            $table->timestamps();

            $table->index('fecha');
        });

        Schema::create('movimientos_caja', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->string('tipo', 10); // ingreso, egreso
            $table->string('concepto', 30); // base, pago, desembolso, gasto, otro
            $table->decimal('monto', 14, 2);
            $table->date('fecha');
            $table->string('descripcion')->nullable();
            $table->foreignId('prestamo_id')->nullable()->constrained('prestamos')->nullOnDelete();
            $table->foreignId('pago_id')->nullable()->constrained('pagos')->nullOnDelete();
            $table->timestamps();

            $table->index(['fecha', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_caja');
        Schema::dropIfExists('pagos');
    }
};
