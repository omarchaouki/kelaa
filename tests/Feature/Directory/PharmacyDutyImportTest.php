<?php

use App\Enums\Role;
use App\Models\Business;
use App\Models\Category;
use App\Models\City;
use App\Models\PharmacyDuty;
use App\Services\Pharmacies\PharmacyDutyImporter;

beforeEach(function () {
    $city = City::factory()->create();
    $category = Category::factory()->create(['slug' => 'pharmacie-de-garde']);
    $this->pharmacy = Business::factory()->for($city)->inCategory($category)->create(['slug' => 'pharmacie-atlas']);
    $this->plumber = Business::factory()->for($city)->create(['slug' => 'plombier-x']);
    $this->importer = app(PharmacyDutyImporter::class);
});

function csvFile(string $content): string
{
    $path = tempnam(sys_get_temp_dir(), 'csv');
    file_put_contents($path, $content);

    return $path;
}

it('imports night duties that end the next morning', function () {
    $result = $this->importer->import(csvFile("pharmacie,date,debut,fin\npharmacie-atlas,2026-10-05,20:00,09:00\n"), 'Africa/Casablanca');

    $duty = PharmacyDuty::query()->sole();

    expect($result)->toBe(['imported' => 1, 'errors' => []])
        ->and($duty->starts_at->format('Y-m-d H:i'))->toBe('2026-10-05 20:00')
        ->and($duty->ends_at->format('Y-m-d H:i'))->toBe('2026-10-06 09:00');
});

it('accepts semicolons and an Excel BOM, and updates an existing duty', function () {
    $this->importer->import(csvFile("\xEF\xBB\xBFpharmacie;date;debut;fin\npharmacie-atlas;2026-10-05;20:00;09:00\n"), 'Africa/Casablanca');
    $this->importer->import(csvFile("pharmacie;date;debut;fin\npharmacie-atlas;2026-10-05;21:00;08:00\n"), 'Africa/Casablanca');

    expect(PharmacyDuty::query()->sole()->starts_at->format('H:i'))->toBe('21:00');
});

it('imports nothing when a single line is invalid', function () {
    $result = $this->importer->import(csvFile(implode("\n", [
        'pharmacie,date,debut,fin',
        'pharmacie-atlas,2026-10-05,20:00,09:00',
        'plombier-x,2026-10-06,20:00,09:00',      // not a pharmacy
        'pharmacie-atlas,2026-02-30,20:00,09:00',  // impossible date
        'pharmacie-atlas,2026-10-07,25:00,09:00',  // invalid time
        '=cmd|calc,2026-10-08,20:00,09:00',        // formula injection attempt
    ])), 'Africa/Casablanca');

    expect($result['imported'])->toBe(0)
        ->and($result['errors'])->toHaveCount(4)
        ->and(PharmacyDuty::query()->count())->toBe(0);
});

it('refuses files without the expected header, too large or with too many rows', function () {
    expect($this->importer->import(csvFile("a,b,c\n1,2,3\n"), 'Africa/Casablanca')['errors'])->not->toBeEmpty()
        ->and($this->importer->import(csvFile(str_repeat('x', PharmacyDutyImporter::MAX_BYTES + 1)), 'Africa/Casablanca')['errors'])->not->toBeEmpty();

    $rows = "pharmacie,date,debut,fin\n".str_repeat("pharmacie-atlas,2026-10-05,20:00,09:00\n", PharmacyDutyImporter::MAX_ROWS + 1);
    expect($this->importer->import(csvFile($rows), 'Africa/Casablanca')['errors'])->not->toBeEmpty();
});

it('lets moderators manage pharmacies de garde in the admin', function () {
    $this->actingAs(staff(Role::Moderator))->get('/admin/pharmacy-duties')->assertOk();
    $this->actingAs(staff(Role::Admin))->get('/admin/pharmacy-duties')->assertOk();
});
