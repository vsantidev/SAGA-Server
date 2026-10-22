<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

// Images envoyées (partenaire, salle ; même code pour profil et animation) :
// A. un fichier qui n'est pas une image est refusé (422), même renommé en .jpg, et rien n'est déposé ;
// B. le SVG est refusé (il peut contenir du script) ;
// C. une vraie image est acceptée sous un nom choisi par le serveur, extension tirée du contenu.
class PictureUploadTest extends TestCase
{
    use DatabaseTransactions;

    private array $created = [];

    protected function setUp(): void
    {
        parent::setUp();

        $admin = User::create([
            'lastname'  => 'TEST',
            'firstname' => 'Images',
            'email'     => 'images-admin-' . uniqid() . '@test.local',
            'password'  => 'secret',
        ]);
        $admin->type = 'admin';
        $admin->save();
        Sanctum::actingAs($admin);
    }

    protected function tearDown(): void
    {
        foreach ($this->created as $path) {
            if (is_file(public_path($path))) {
                unlink(public_path($path));
            }
        }
        parent::tearDown();
    }

    private function sponsorWith(UploadedFile $file)
    {
        return $this->post('/api/sponsors/create', [
            'name'    => 'sponsor-image-' . uniqid(),
            'content' => 'Une présentation',
            'link'    => 'https://www.exemple.fr',
            'picture' => $file,
        ], ['Accept' => 'application/json']);
    }

    private function filesIn(string $folder): array
    {
        return is_dir(public_path("images/$folder")) ? scandir(public_path("images/$folder")) : [];
    }

    // 1 — Règle A : un script PHP est refusé et n'arrive pas dans public/images
    public function test_php_file_is_refused(): void
    {
        $before = $this->filesIn('sponsors');

        $this->sponsorWith(UploadedFile::fake()->createWithContent('shell.php', '<?php echo "x"; ?>'))
            ->assertStatus(422)
            ->assertJsonPath('errors.picture.0', "L'image doit être au format JPG, PNG, GIF ou WebP.");

        $this->assertSame($before, $this->filesIn('sponsors'));
    }

    // 2 — Règle A : le contenu compte, pas le nom
    public function test_php_content_named_jpg_is_refused(): void
    {
        // Vrai fichier temporaire : le type est lu dans le contenu, comme pour un envoi réel
        $path = tempnam(sys_get_temp_dir(), 'img');
        file_put_contents($path, '<?php echo "x"; ?>');

        $this->sponsorWith(new UploadedFile($path, 'photo.jpg', null, null, true))
            ->assertStatus(422);
    }

    // 3 — Règle B
    public function test_svg_is_refused(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>';

        $this->sponsorWith(UploadedFile::fake()->createWithContent('logo.svg', $svg))
            ->assertStatus(422);
    }

    // 4 — Règle C : nom généré, extension réelle, fichier bien déposé
    public function test_real_image_is_saved_under_server_name(): void
    {
        $response = $this->sponsorWith(UploadedFile::fake()->image('Mon logo.php.png', 40, 40));

        $response->assertStatus(201);
        $picture = \App\Models\Sponsor::latest('id')->value('picture');
        $this->created[] = $picture;

        $this->assertMatchesRegularExpression('#^images/sponsors/[0-9a-f]{13}\.png$#', $picture);
        $this->assertFileExists(public_path($picture));
    }

    // 5 — Règle A sur la salle : refus 422 détaillé, pas une erreur 500
    public function test_room_refuses_non_image_with_422(): void
    {
        $this->post('/api/rooms/rooms', [
            'name'    => 'Salle image ' . uniqid(),
            'picture' => UploadedFile::fake()->createWithContent('plan.php', '<?php echo "x"; ?>'),
        ], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['picture']]);
    }
}
