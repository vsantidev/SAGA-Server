<?php

namespace Tests\Feature;

use App\Models\Sponsor;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

// Création et mise à jour d'un partenaire :
// A. un partenaire complet est créé (201), puis mis à jour (200) ;
// B. un champ manquant ou invalide est refusé avec un message clair (422), rien n'est écrit.
// Les tests n'envoient pas d'image : aucun fichier n'est déposé.
class SponsorTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $admin = User::create([
            'lastname'  => 'TEST',
            'firstname' => 'Admin',
            'email'     => 'sponsor-admin-' . uniqid() . '@test.local',
            'password'  => 'secret',
        ]);
        $admin->type = 'admin';
        $admin->save();
        Sanctum::actingAs($admin);
    }

    private function create(array $data)
    {
        return $this->postJson('/api/sponsors/create', $data);
    }

    private function update(Sponsor $sponsor, array $changes)
    {
        $payload = array_merge($sponsor->only(['id', 'name', 'content', 'link', 'picture']), $changes);

        return $this->postJson('/api/sponsors/edit/' . $sponsor->id, ['sponsor' => json_encode($payload)]);
    }

    private function newSponsor(): Sponsor
    {
        return Sponsor::create([
            'name'    => 'sponsor-test-' . uniqid(),
            'content' => 'Présentation de départ',
            'link'    => 'https://www.exemple.fr',
            'picture' => 'images/sponsors/img_default.jpg',
        ]);
    }

    // 1 — Règle A
    public function test_complete_sponsor_is_created(): void
    {
        $name = 'sponsor-test-' . uniqid();

        $this->create(['name' => $name, 'content' => 'Une présentation', 'link' => 'https://www.exemple.fr'])
            ->assertStatus(201);

        $this->assertDatabaseHas('sponsors', [
            'name'    => $name,
            'content' => 'Une présentation',
            'picture' => 'images/sponsors/img_default.jpg',
        ]);
    }

    public function test_link_is_optional(): void
    {
        $name = 'sponsor-test-' . uniqid();

        $this->create(['name' => $name, 'content' => 'Une présentation', 'link' => ''])->assertStatus(201);

        $this->assertDatabaseHas('sponsors', ['name' => $name, 'link' => null]);
    }

    // 2 — Règle B : une présentation vide finissait en erreur 500
    public function test_sponsor_without_content_is_refused(): void
    {
        $name = 'sponsor-test-' . uniqid();

        $this->create(['name' => $name])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['content' => 'Merci de renseigner la présentation du partenaire']);

        $this->assertDatabaseMissing('sponsors', ['name' => $name]);
    }

    public function test_sponsor_without_name_is_refused(): void
    {
        $this->create(['content' => 'Une présentation'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name' => 'Merci de renseigner le nom du partenaire']);
    }

    public function test_too_long_name_is_refused(): void
    {
        $this->create(['name' => str_repeat('a', 251), 'content' => 'Une présentation'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_incomplete_link_is_refused(): void
    {
        $name = 'sponsor-test-' . uniqid();

        $this->create(['name' => $name, 'content' => 'Une présentation', 'link' => 'www.exemple.fr'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['link' => 'Le lien doit être une adresse complète, commençant par https://']);

        $this->assertDatabaseMissing('sponsors', ['name' => $name]);
    }

    // 3 — Mise à jour
    public function test_update_answers_200_and_saves(): void
    {
        $sponsor = $this->newSponsor();

        $this->update($sponsor, ['content' => 'Nouvelle présentation'])->assertStatus(200);

        $this->assertSame('Nouvelle présentation', $sponsor->fresh()->content);
    }

    public function test_update_with_empty_content_is_refused(): void
    {
        $sponsor = $this->newSponsor();

        $this->update($sponsor, ['content' => ''])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['content']);

        $this->assertSame('Présentation de départ', $sponsor->fresh()->content);
    }

    public function test_update_with_incomplete_link_is_refused(): void
    {
        $sponsor = $this->newSponsor();

        $this->update($sponsor, ['link' => 'www.autre.fr'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['link']);

        $this->assertSame('https://www.exemple.fr', $sponsor->fresh()->link);
    }
}
