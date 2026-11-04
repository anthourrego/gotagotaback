<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Ruta;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PrestamoFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_flujo_prestamo_y_pagos(): void
    {
        $cobrador = User::factory()->create(['role' => 'cobrador']);
        Sanctum::actingAs($cobrador);
        $ruta = Ruta::create(['nombre' => 'Centro', 'cobrador_id' => $cobrador->id]);
        $cliente = Cliente::create(['nombre' => 'Juan', 'documento' => '123', 'ruta_id' => $ruta->id]);

        $res = $this->postJson('/api/prestamos', [
            'cliente_id' => $cliente->id,
            'monto' => 100000,
            'tasa_interes' => 20,
            'numero_cuotas' => 10,
            'frecuencia' => 'diario',
            'fecha_desembolso' => '2026-11-02',
            'fecha_primera_cuota' => '2026-11-03',
        ])->assertCreated();

        $id = $res->json('id');
        $this->assertEquals(120000, $res->json('total_a_pagar'));
        $this->assertCount(10, $res->json('cuotas'));

        $this->postJson("/api/prestamos/$id/pagos", ['monto' => 18000])->assertCreated();
        $this->postJson("/api/prestamos/$id/pagos", ['monto' => 999999])->assertStatus(422);

        $show = $this->getJson("/api/prestamos/$id")->assertOk();
        $this->assertEquals(102000, $show->json('saldo'));
        $this->assertEquals('pagada', $show->json('cuotas.0.estado'));
        $this->assertEquals('parcial', $show->json('cuotas.1.estado'));

        $this->postJson("/api/prestamos/$id/pagos", ['monto' => 102000])->assertCreated();
        $this->assertEquals('pagado', $this->getJson("/api/prestamos/$id")->json('estado'));

        $caja = $this->getJson('/api/caja')->assertOk();
        $this->assertEquals(120000, $caja->json('ingresos'));
    }

    public function test_cobrador_no_accede_a_usuarios(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'cobrador']));
        $this->getJson('/api/usuarios')->assertForbidden();
    }
}
