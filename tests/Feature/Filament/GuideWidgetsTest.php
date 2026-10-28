<?php

namespace Tests\Feature\Filament;

use App\Enums\UserRole;
use App\Filament\Widgets\GuideRedacteurWidget;
use App\Filament\Widgets\GuideRelecteurWidget;
use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class GuideWidgetsTest extends TestCase
{
    public function test_le_guide_relecteur_n_est_visible_que_du_relecteur(): void
    {
        foreach ([UserRole::Webmaster, UserRole::Redacteur, UserRole::Relecteur, UserRole::Admin] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]));

            $this->assertSame(
                $role === UserRole::Relecteur,
                GuideRelecteurWidget::canView(),
                "Rôle {$role->value}",
            );
        }
    }

    public function test_le_guide_redacteur_n_est_visible_que_du_redacteur(): void
    {
        foreach ([UserRole::Webmaster, UserRole::Redacteur, UserRole::Relecteur, UserRole::Admin] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]));

            $this->assertSame(
                $role === UserRole::Redacteur,
                GuideRedacteurWidget::canView(),
                "Rôle {$role->value}",
            );
        }
    }

    public function test_le_guide_relecteur_s_affiche(): void
    {
        $this->actingAs(User::factory()->create(['role' => UserRole::Relecteur]));

        Livewire::test(GuideRelecteurWidget::class)
            ->assertSee('Guide rapide — Relecteur');
    }
}
