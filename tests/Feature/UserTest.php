<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;

class UserTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function puede_crear_usuario()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/crearUsuario', [
            'name' => 'Test User',
            'email' => 'nuevo@test.com',
            'password' => 'password123',
            'saldo' => 100
        ]);

        $response->assertStatus(201)
                 ->assertJson([
                     'message' => 'Usuario creado exitosamente'
                 ]);

        $this->assertDatabaseHas('users', [
            'email' => 'nuevo@test.com',
            'saldo' => 100
        ]);
    }

    /** @test */
    public function no_puede_crear_usuario_con_email_duplicado()
    {
        $user = User::factory()->create(['email' => 'test@test.com']);
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/crearUsuario', [
            'name' => 'Test User',
            'email' => 'test@test.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function puede_hacer_login()
    {
        User::factory()->create([
            'email' => 'test@test.com',
            'password' => bcrypt('password123')
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'test@test.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'access_token',
                     'token_type',
                     'user'
                 ]);
    }

    /** @test */
    public function falla_login_con_credenciales_incorrectas()
    {
        User::factory()->create([
            'email' => 'test@test.com',
            'password' => bcrypt('password123')
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'test@test.com',
            'password' => 'wrongpassword'
        ]);

        $response->assertStatus(401)
                 ->assertJson([
                     'message' => 'Credenciales incorrectas'
                 ]);
    }
}
