<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LanguageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_switch_locale(): void
    {
        $this->post('/language/ms')
            ->assertRedirect()
            ->assertSessionHas('locale', 'ms');
    }

    public function test_switching_back_to_default_locale(): void
    {
        $this->withSession(['locale' => 'ms'])
            ->post('/language/en')
            ->assertSessionHas('locale', 'en');
    }

    public function test_unknown_locale_is_rejected(): void
    {
        $this->post('/language/de')->assertNotFound();
    }

    public function test_active_locale_and_translations_are_shared(): void
    {
        $this->withSession(['locale' => 'ms'])
            ->get('/login')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('locale', 'ms')
                ->has('translations')
                ->where('translations.Dashboard', 'Papan Pemuka')
                ->where('translations.Log in', 'Log Masuk')
            );
    }

    public function test_english_dictionary_falls_back_to_english_keys(): void
    {
        $this->withSession(['locale' => 'en'])
            ->get('/login')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('locale', 'en')
                ->where('translations.Dashboard', 'Dashboard')
            );
    }
}
