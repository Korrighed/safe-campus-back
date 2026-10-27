<?php

namespace Tests\Feature\Database;

use App\Models\Signalement;
use App\Models\SousTheme;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SignalementTest extends TestCase
{
    public function test_un_signalement_se_rattache_a_un_sous_theme(): void
    {
        $sousTheme = SousTheme::factory()->avecSignalement()->create(['libelle' => 'Alcool']);
        $signalement = Signalement::factory()->create(['sous_theme_id' => $sousTheme->id]);

        $this->assertSame('Alcool', $signalement->sousTheme->libelle);
        $this->assertCount(1, $sousTheme->signalements);
    }

    public function test_le_signalement_est_anonyme_par_construction(): void
    {
        // Token libre, aucune colonne ne relie un signalement a un utilisateur.
        $colonnes = Schema::getColumnListing('signalements');

        $this->assertContains('token_antispam', $colonnes);
        $this->assertNotContains('user_id', $colonnes);
        $this->assertNotContains('contact_id', $colonnes);
    }

    public function test_la_date_heure_est_castee(): void
    {
        $signalement = Signalement::factory()->create();

        $this->assertInstanceOf(Carbon::class, $signalement->fresh()->date_heure);
    }

    public function test_supprimer_un_sous_theme_supprime_ses_signalements(): void
    {
        $sousTheme = SousTheme::factory()->avecSignalement()->create();
        Signalement::factory()->count(3)->create(['sous_theme_id' => $sousTheme->id]);

        $sousTheme->delete();

        $this->assertDatabaseCount('signalements', 0);
    }
}
