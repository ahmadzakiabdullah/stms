<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleSwitchTest extends TestCase
{
    use RefreshDatabase;

    public function test_locale_can_be_switched_to_bahasa_malaysia(): void
    {
        $response = $this
            ->from('/login')
            ->post(route('locale.update'), [
                'locale' => 'ms',
            ]);

        $response->assertRedirect('/login');
        $response->assertSessionHas('locale', 'ms');
    }

    public function test_invalid_locale_is_rejected(): void
    {
        $response = $this
            ->from('/login')
            ->post(route('locale.update'), [
                'locale' => 'fr',
            ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('locale');
    }

    public function test_selected_locale_is_applied_to_first_page_render(): void
    {
        $response = $this
            ->withSession(['locale' => 'ms'])
            ->get('/login');

        $response->assertOk();
        $response->assertSee('lang="ms"', false);
    }
}
