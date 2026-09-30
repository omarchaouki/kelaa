<?php

use App\Enums\ReviewStatus;
use App\Livewire\Reviews\ReviewForm;
use App\Models\Business;
use App\Models\City;
use App\Models\Review;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('public');
    Storage::fake('local');
    $this->business = Business::factory()->for(City::factory())->create();
    $this->author = User::factory()->create(['created_at' => now()->subMonth()]);
});

/**
 * A real JPEG with an EXIF segment carrying a fake GPS marker.
 */
function jpegWithExif(): UploadedFile
{
    $image = imagecreatetruecolor(64, 48);
    ob_start();
    imagejpeg($image);
    $jpeg = (string) ob_get_clean();

    $exif = "Exif\0\0GPSLatitude=33.5731;GPSLongitude=-7.5898;SerialNumber=SECRET-DEVICE";
    $segment = "\xFF\xE1".pack('n', strlen($exif) + 2).$exif;
    $withExif = substr($jpeg, 0, 2).$segment.substr($jpeg, 2);

    return UploadedFile::fake()->createWithContent('photo.jpg', $withExif);
}

it('publishes a review from the form', function () {
    Livewire::actingAs($this->author)
        ->test(ReviewForm::class, ['business' => $this->business])
        ->set('rating', 5)
        ->set('body', 'Accueil très sympathique et travail impeccable.')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('flash', __('Merci ! Votre avis est publié.'));

    expect(Review::query()->sole()->status)->toBe(ReviewStatus::Published);
});

it('validates rating and minimum text length', function () {
    Livewire::actingAs($this->author)
        ->test(ReviewForm::class, ['business' => $this->business])
        ->set('rating', 0)
        ->set('body', 'Trop court')
        ->call('save')
        ->assertHasErrors(['rating', 'body']);

    expect(Review::query()->count())->toBe(0);
});

it('accepts a rating without text', function () {
    Livewire::actingAs($this->author)
        ->test(ReviewForm::class, ['business' => $this->business])
        ->set('rating', 3)
        ->call('save')
        ->assertHasNoErrors();

    expect(Review::query()->sole()->body)->toBeNull();
});

it('strips EXIF metadata (GPS, device) from uploaded photos', function () {
    Livewire::actingAs($this->author)
        ->test(ReviewForm::class, ['business' => $this->business])
        ->set('rating', 4)
        ->set('photos', [jpegWithExif()])
        ->call('save')
        ->assertHasNoErrors();

    $media = Review::query()->sole()->getFirstMedia('photos');
    $stored = file_get_contents($media->getPath());

    expect($stored)->not->toContain('Exif', 'GPSLatitude', 'SECRET-DEVICE')
        ->and($media->file_name)->not->toBe('photo.jpg')
        ->and(getimagesize($media->getPath())[2])->toBe(IMAGETYPE_JPEG);
});

it('refuses non-image uploads and too many photos', function () {
    $script = UploadedFile::fake()->createWithContent('photo.jpg', '<?php echo "pwned";');

    Livewire::actingAs($this->author)
        ->test(ReviewForm::class, ['business' => $this->business])
        ->set('rating', 4)
        ->set('photos', [$script])
        ->call('save')
        ->assertHasErrors('photos.0');

    Livewire::actingAs($this->author)
        ->test(ReviewForm::class, ['business' => $this->business])
        ->set('rating', 4)
        ->set('photos', array_map(fn () => UploadedFile::fake()->image('a.jpg'), range(1, 6)))
        ->call('save')
        ->assertHasErrors('photos');
});

it('cannot delete photos of another review', function () {
    $other = Review::factory()->for($this->business)->create();
    $other->addMedia(UploadedFile::fake()->image('x.jpg'))->toMediaCollection('photos');
    $otherMediaId = $other->getFirstMedia('photos')->id;

    Livewire::actingAs($this->author)
        ->test(ReviewForm::class, ['business' => $this->business])
        ->set('rating', 4)
        ->set('removePhotoIds', [$otherMediaId])
        ->call('save');

    expect($other->fresh()->getMedia('photos'))->toHaveCount(1);
});

it('sends guests to the login page', function () {
    Livewire::test(ReviewForm::class, ['business' => $this->business])
        ->call('save')
        ->assertRedirect(route('login'));
});
