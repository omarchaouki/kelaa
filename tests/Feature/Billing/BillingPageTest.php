<?php

use App\Actions\Billing\HandlePayment;
use App\Actions\Billing\PlaceOrder;
use App\Enums\BillingPeriod;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\Role;
use App\Filament\Pages\PlacementCalendar;
use App\Filament\Resources\Payments\PaymentResource;
use App\Livewire\Owners\BillingPage;
use App\Models\Business;
use App\Models\Category;
use App\Models\City;
use App\Models\Payment;
use App\Models\PlacementRate;
use App\Models\Plan;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;

beforeEach(function () {
    Notification::fake();
    Storage::fake('local');
    Plan::factory()->free()->create(['slug' => 'free']);
    $this->category = Category::factory()->create();
    $this->owner = staff(Role::BusinessOwner);
    $this->business = Business::factory()->for(City::factory()->create(['slug' => 'ma-ville']))->inCategory($this->category)
        ->create(['owner_id' => $this->owner->id, 'is_claimed' => true]);
    $this->plan = Plan::factory()->create(['price_monthly' => '149.00']);
    PlacementRate::query()->create(['position' => 1, 'price_week' => 250, 'price_month' => 900, 'neighborhood_percent' => 60, 'is_active' => true]);
});

it('opens the billing page only to the owner', function () {
    $url = '/fr/dashboard/'.$this->business->id.'/facturation';

    $this->actingAs($this->owner)->get($url)->assertOk()->assertSee('Formules et paiements')->assertSee('Sponsorisé');
    $this->actingAs(staff(Role::BusinessOwner))->get($url)->assertForbidden();
    auth()->logout();
    $this->get($url)->assertRedirect();
});

it('orders a plan and shows the payment instructions with the server price', function () {
    config(['billing.manual.rib' => '011 780 0000123456789012 34']);

    Livewire::actingAs($this->owner)->test(BillingPage::class, ['business' => $this->business])
        ->set('planId', $this->plan->id)
        ->set('period', 'monthly')
        ->call('orderPlan')
        ->assertHasNoErrors()
        ->assertSee('149')
        ->assertSee('011 780 0000123456789012 34')
        ->assertSee(Payment::query()->sole()->reference);

    expect(Payment::query()->sole()->amount)->toBe('149.00');
});

it('rejects tampered order input', function () {
    $foreignCategory = Category::factory()->create();

    Livewire::actingAs($this->owner)->test(BillingPage::class, ['business' => $this->business])
        ->set('planId', 999999)->call('orderPlan')->assertHasErrors('planId')
        ->set('planId', $this->plan->id)->set('method', 'cmi')->call('orderPlan')->assertHasErrors('method')
        ->set('method', 'bank_transfer')
        ->set('categoryId', $foreignCategory->id)->call('orderPlacement')->assertHasErrors('categoryId')
        ->set('categoryId', $this->category->id)->set('position', 9)->call('orderPlacement')->assertHasErrors('position')
        ->set('position', 1)->set('units', 50)->call('orderPlacement')->assertHasErrors('units');

    expect(Payment::query()->count())->toBe(0);
});

it('cannot be pointed at another business', function () {
    $other = Business::factory()->create(['owner_id' => staff(Role::BusinessOwner)->id]);

    Livewire::actingAs($this->owner)->test(BillingPage::class, ['business' => $this->business])
        ->set('businessId', $other->id);
})->throws(CannotUpdateLockedPropertyException::class);

it('uploads a receipt privately and only for the owner’s own pending payment', function () {
    $payment = app(PlaceOrder::class)->subscription($this->owner, $this->business, $this->plan, BillingPeriod::Monthly, PaymentMethod::BankTransfer);
    $otherPayment = Payment::factory()->create();

    Livewire::actingAs($this->owner)->test(BillingPage::class, ['business' => $this->business])
        ->set("receipts.{$payment->id}", UploadedFile::fake()->image('recu.jpg', 600, 800))
        ->call('uploadReceipt', $payment->id)
        ->assertHasNoErrors();

    $media = $payment->fresh()->getFirstMedia('receipt');

    expect($media)->not->toBeNull()
        ->and($media->disk)->toBe('local')
        ->and($media->file_name)->not->toContain('recu');

    // Not reachable by the public, nor by moderators; admins with payment rights can see it.
    $this->get(route('admin.private-media', $media))->assertNotFound();
    $this->actingAs(staff(Role::Moderator))->get(route('admin.private-media', $media))->assertNotFound();
    $this->actingAs(staff(Role::Admin))->get(route('admin.private-media', $media))->assertOk();

    Livewire::actingAs($this->owner)->test(BillingPage::class, ['business' => $this->business])
        ->set("receipts.{$otherPayment->id}", UploadedFile::fake()->image('x.jpg'))
        ->call('uploadReceipt', $otherPayment->id)
        ->assertNotFound();

    expect($otherPayment->fresh()->getMedia('receipt'))->toHaveCount(0);
});

it('rejects receipts that are not PDF or images', function () {
    $payment = app(PlaceOrder::class)->subscription($this->owner, $this->business, $this->plan, BillingPeriod::Monthly, PaymentMethod::BankTransfer);

    Livewire::actingAs($this->owner)->test(BillingPage::class, ['business' => $this->business])
        ->set("receipts.{$payment->id}", UploadedFile::fake()->createWithContent('recu.html', '<script>alert(1)</script>'))
        ->call('uploadReceipt', $payment->id)
        ->assertHasErrors("receipts.{$payment->id}");

    expect($payment->fresh()->getMedia('receipt'))->toHaveCount(0);
});

it('lets only the owner download their invoice as a PDF', function () {
    $payment = app(PlaceOrder::class)->subscription($this->owner, $this->business, $this->plan, BillingPeriod::Monthly, PaymentMethod::BankTransfer);
    app(HandlePayment::class)->validate(staff(Role::Admin), $payment);
    $invoice = $payment->fresh()->invoice;
    $url = '/fr/dashboard/'.$this->business->id.'/factures/'.$invoice->id;

    $response = $this->actingAs($this->owner)->get($url)->assertOk()->assertHeader('Content-Type', 'application/pdf');
    expect($response->headers->get('Cache-Control'))->toContain('no-store');

    $this->actingAs(staff(Role::BusinessOwner))->get($url)->assertForbidden();

    // An invoice of another business through this business's URL.
    $other = Business::factory()->create(['owner_id' => $this->owner->id, 'is_claimed' => true]);
    $this->actingAs($this->owner)->get('/fr/dashboard/'.$other->id.'/factures/'.$invoice->id)->assertNotFound();

    $this->actingAs(staff(Role::Moderator))->get(route('admin.invoice', $invoice))->assertNotFound();
    $this->actingAs(staff(Role::Admin))->get(route('admin.invoice', $invoice))->assertOk();
});

it('keeps the card gateway closed until it is integrated', function () {
    $this->post('/payments/cmi/callback', ['oid' => 'P-AAAA', 'ProcReturnCode' => '00'])->assertNotFound();
    $this->get('/payments/cmi/ok?oid=P-AAAA')->assertNotFound();

    expect(Payment::query()->where('status', PaymentStatus::Validated)->count())->toBe(0);
});

it('keeps payment administration for admins, not moderators', function () {
    $this->actingAs(staff(Role::Moderator))->get(PaymentResource::getUrl('index'))->assertForbidden();
    $this->actingAs(staff(Role::Moderator))->get(PlacementCalendar::getUrl())->assertForbidden();

    $this->actingAs(staff(Role::Admin))->get(PaymentResource::getUrl('index'))->assertOk();
    $this->actingAs(staff(Role::Admin))->get(PlacementCalendar::getUrl(['categoryId' => $this->category->id]))->assertOk()->assertSee('Position 1');
});
