<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfilePhotoUploadSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_profile_photo_with_embedded_php_is_rejected_before_replacing_the_current_photo(): void
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'profile_photo_path' => 'profile-photos/current.png',
        ]);

        Storage::disk('public')->put('profile-photos/current.png', 'existing photo');

        $png = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='
        );

        $file = UploadedFile::fake()->createWithContent(
            'avatar.png',
            $png."<?php echo 'safe'; ?>"
        );

        $response = $this->actingAs($user)->put(route('profile.update'), [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'profile_photo' => $file,
        ]);

        $response->assertSessionHasErrors([
            'profile_photo' => 'The file contains suspicious content and cannot be uploaded.',
        ]);

        $this->assertSame('profile-photos/current.png', $user->fresh()->profile_photo_path);
        Storage::disk('public')->assertExists('profile-photos/current.png');
        $this->assertDatabaseCount('media_files', 0);
    }
}
