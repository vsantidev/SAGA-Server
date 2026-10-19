<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

// Import CSV (uploadcsv) : les noms et prénoms accentués sont mis en forme correctement.
class UserImportNamesTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $admin = User::create([
            'lastname'  => 'TEST',
            'firstname' => 'Admin',
            'email'     => 'import-noms-admin-' . uniqid() . '@test.local',
            'password'  => 'secret',
        ]);
        // 'type' n'est pas mass-assignable : on le pose directement.
        $admin->type = 'admin';
        $admin->save();
        Sanctum::actingAs($admin);
    }

    private function importOne(string $lastname, string $firstname): User
    {
        $email = 'import-noms-' . uniqid() . '@test.local';
        $this->postJson('/api/user/userimport', [[
            'lastname'  => $lastname,
            'firstname' => $firstname,
            'email'     => $email,
        ]])->assertStatus(200);

        return User::where('email', $email)->firstOrFail();
    }

    public function test_accented_last_name_is_fully_uppercased(): void
    {
        $user = $this->importOne('Lefèvre', 'José');

        $this->assertSame('LEFÈVRE', $user->lastname);
    }

    public function test_accented_first_name_keeps_its_accent_in_lowercase(): void
    {
        $user = $this->importOne('Martin', 'JOSÉ');

        $this->assertSame('José', $user->firstname);
    }

    public function test_compound_first_name_gets_a_capital_after_the_hyphen(): void
    {
        $user = $this->importOne('Durand', 'jean-pierre');

        $this->assertSame('Jean-Pierre', $user->firstname);
    }

    public function test_plain_names_are_unchanged(): void
    {
        $user = $this->importOne('dupont', 'MARIE');

        $this->assertSame('DUPONT', $user->lastname);
        $this->assertSame('Marie', $user->firstname);
    }
}
