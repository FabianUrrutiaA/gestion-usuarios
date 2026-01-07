<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Transferencia;

class TransferenciaTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $token;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Crear usuario de prueba y obtener token
        $this->user = User::factory()->create([
            'email' => 'test@test.com',
            'password' => bcrypt('password123'),
            'saldo' => 10000
        ]);
        
        $this->token = $this->user->createToken('test-token')->plainTextToken;
    }

    /** @test */
    public function puede_crear_transferencia_valida()
    {
        $receptor = User::factory()->create(['saldo' => 0]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/crearTransferencia', [
            'id_emisor' => $this->user->id,
            'id_receptor' => $receptor->id,
            'monto' => 100
        ]);

        $response->assertStatus(201)
                 ->assertJson([
                     'message' => 'Transferencia realizada exitosamente'
                 ]);

        $this->assertDatabaseHas('transferencia', [
            'id_emisor' => $this->user->id,
            'id_receptor' => $receptor->id,
            'monto' => 100
        ]);
    }

    /** @test */
    public function no_puede_transferir_sin_saldo_suficiente()
    {
        $receptor = User::factory()->create();
        
        $this->user->update(['saldo' => 50]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/crearTransferencia', [
            'id_emisor' => $this->user->id,
            'id_receptor' => $receptor->id,
            'monto' => 100
        ]);

        $response->assertStatus(400)
                 ->assertJson([
                     'message' => 'Saldo insuficiente para realizar la transferencia'
                 ]);
    }

    /** @test */
    public function no_puede_exceder_limite_diario_5000()
    {
        $receptor = User::factory()->create();

        // Primera transferencia de 5000
        $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/crearTransferencia', [
            'id_emisor' => $this->user->id,
            'id_receptor' => $receptor->id,
            'monto' => 5000
        ]);

        // Intentar segunda transferencia
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/crearTransferencia', [
            'id_emisor' => $this->user->id,
            'id_receptor' => $receptor->id,
            'monto' => 100
        ]);

        $response->assertStatus(400)
                 ->assertJson([
                     'message' => 'Has excedido el límite diario de transferencias de 5,000 USD'
                 ]);
    }

    /** @test */
    public function no_puede_transferir_monto_mayor_a_5000()
    {
        $receptor = User::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/crearTransferencia', [
            'id_emisor' => $this->user->id,
            'id_receptor' => $receptor->id,
            'monto' => 5001
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['monto']);
    }

    /** @test */
    public function no_puede_transferir_a_si_mismo()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/crearTransferencia', [
            'id_emisor' => $this->user->id,
            'id_receptor' => $this->user->id,
            'monto' => 100
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['id_receptor']);
    }

    /** @test */
    public function detecta_transferencias_duplicadas()
    {
        $receptor = User::factory()->create();

        // Primera transferencia
        $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/crearTransferencia', [
            'id_emisor' => $this->user->id,
            'id_receptor' => $receptor->id,
            'monto' => 100
        ]);

        // Intentar transferencia duplicada
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/crearTransferencia', [
            'id_emisor' => $this->user->id,
            'id_receptor' => $receptor->id,
            'monto' => 100
        ]);

        $response->assertStatus(409)
                 ->assertJson([
                     'message' => 'Esta transferencia ya fue procesada recientemente. Transferencia duplicada detectada.'
                 ]);
    }
}