<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicFrontendTest extends TestCase
{
    /**
     * @test
     */
    public function it_renders_the_polished_login_screen(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertSee(__('auth.login'));
        $response->assertSee(__('auth.secure_access_note'));
        $response->assertSee('name="remember"', false);
    }

    /**
     * @test
     */
    public function it_renders_the_public_welcome_screen(): void
    {
        app()->setLocale('ar');

        $html = view('welcome')->render();

        $this->assertStringContainsString(__('welcome.hero_title'), $html);
        $this->assertStringContainsString(__('welcome.actions.login'), $html);
    }
}
