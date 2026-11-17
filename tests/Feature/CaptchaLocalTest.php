<?php

namespace Tests\Feature;

use App\Services\CaptchaLocal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Captcha local (sin internet). */
class CaptchaLocalTest extends TestCase
{
    use RefreshDatabase;

    public function test_genera_imagen(): void
    {
        $this->getJson('/api/captcha')
            ->assertOk()
            ->assertJsonStructure(['id', 'imagen', 'expira_en']);
    }

    public function test_respuesta_correcta_sirve_una_sola_vez(): void
    {
        $captcha = CaptchaLocal::crear('AB3XK');

        $this->assertTrue(CaptchaLocal::verificar($captcha['id'], 'ab3xk'));
        $this->assertFalse(CaptchaLocal::verificar($captcha['id'], 'AB3XK'));
    }

    public function test_respuesta_incorrecta_no_pasa(): void
    {
        $captcha = CaptchaLocal::crear('AB3XK');

        $this->assertFalse(CaptchaLocal::verificar($captcha['id'], 'ZZZZZ'));
    }

    public function test_login_exige_captcha_cuando_esta_activo(): void
    {
        config(['services.captcha.enabled' => true]);

        $this->postJson('/api/login', ['email' => 'a@b.com', 'password' => 'x'])
            ->assertStatus(422)
            ->assertJsonPath('codigo', 'captcha_requerido');

        $this->postJson('/api/login', ['email' => 'a@b.com', 'password' => 'x', 'captcha_token' => 'id-falso:ABCDE'])
            ->assertStatus(422)
            ->assertJsonPath('codigo', 'captcha_invalido');
    }
}
