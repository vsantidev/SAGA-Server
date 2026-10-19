<?php

namespace Tests\Feature;

use App\Models\Evenement;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

// Lecture du fichier d'import (uploadcsv) : lignes vides ignorées, fichier refusé en entier
// si une ligne est invalide, jamais d'import partiel ni d'erreur 500.
class UserImportCsvTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $admin = User::create([
            'lastname'  => 'TEST',
            'firstname' => 'Admin',
            'email'     => 'csv-admin-' . uniqid() . '@test.local',
            'password'  => 'secret',
        ]);
        // 'type' n'est pas mass-assignable : on le pose directement.
        $admin->type = 'admin';
        $admin->save();
        Sanctum::actingAs($admin);
    }

    private function row(string $email): array
    {
        return ['lastname' => 'Csv', 'firstname' => 'Test', 'email' => $email];
    }

    private function email(): string
    {
        return 'csv-' . uniqid() . '@test.local';
    }

    // Saut de ligne en fin de fichier : le lecteur CSV de l'écran envoie une dernière ligne vide
    public function test_trailing_empty_line_is_ignored(): void
    {
        $email = $this->email();

        $this->postJson('/api/user/userimport', [$this->row($email), ['lastname' => '']])
            ->assertStatus(200)
            ->assertJsonPath('crees', 1);

        $this->assertDatabaseHas('users', ['email' => $email]);
    }

    // Une ligne sans e-mail au milieu du fichier : refus, aucun compte créé
    public function test_invalid_line_rejects_the_whole_file(): void
    {
        $premier = $this->email();
        $dernier = $this->email();

        $response = $this->postJson('/api/user/userimport', [
            $this->row($premier),
            ['lastname' => 'Sans', 'firstname' => 'Mail', 'email' => ''],
            $this->row($dernier),
        ])->assertStatus(422);

        $this->assertStringContainsString('ligne 3', $response->json('erreurs.0'));
        $this->assertDatabaseMissing('users', ['email' => $premier]);
        $this->assertDatabaseMissing('users', ['email' => $dernier]);
    }

    // Mauvais en-têtes (« Nom;Prénom;Mail ») : refus clair, rien n'est créé
    public function test_wrong_headers_are_refused(): void
    {
        $this->postJson('/api/user/userimport', [['Nom' => 'Csv', 'Prénom' => 'Test', 'Mail' => $this->email()]])
            ->assertStatus(422);
    }

    // Fichier vide : refus clair au lieu d'une 500
    public function test_empty_file_is_refused(): void
    {
        $this->postJson('/api/user/userimport', [])->assertStatus(422);
    }

    // Sans convention active : refus avant toute écriture
    public function test_import_without_active_event_is_refused(): void
    {
        $email = $this->email();
        Evenement::where('actif', 1)->update(['actif' => 0]);

        $this->postJson('/api/user/userimport', [$this->row($email)])->assertStatus(422);

        $this->assertDatabaseMissing('users', ['email' => $email]);
    }
}
