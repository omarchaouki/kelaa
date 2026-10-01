<?php

namespace App\Livewire\Owners;

use App\Actions\Billing\PlaceOrder;
use App\Actions\Billing\UploadReceipt;
use App\Enums\BillingPeriod;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PlacementUnit;
use App\Exceptions\RateLimited;
use App\Exceptions\SlotUnavailable;
use App\Models\Business;
use App\Models\Category;
use App\Models\Neighborhood;
use App\Models\Payment;
use App\Models\Plan;
use App\Services\Billing\Gateways\ManualGateway;
use App\Services\Billing\PlacementInventory;
use App\Services\Billing\Pricing;
use App\Services\Plans\PlanFeatures;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * Plans, sponsored placements, payments and invoices of one business.
 */
#[Layout('layouts.app')]
class BillingPage extends Component
{
    use WithFileUploads;

    #[Locked]
    public int $businessId;

    // Plan order.
    public ?int $planId = null;

    public string $period = 'monthly';

    public string $method = 'bank_transfer';

    // Placement order.
    public ?int $categoryId = null;

    public ?int $neighborhoodId = null;

    public int $position = 1;

    public string $unit = 'week';

    public int $units = 1;

    public string $startDate = '';

    /** @var array<int, TemporaryUploadedFile|null> Receipts keyed by payment id. */
    public array $receipts = [];

    // Legal info printed on invoices.
    public string $legalName = '';

    public string $ice = '';

    public string $legalAddress = '';

    public ?string $flash = null;

    public function mount(Business $business): void
    {
        Gate::authorize('manage', $business);

        $this->businessId = $business->id;
        $this->categoryId = $business->categories()->wherePivot('is_primary', true)->value('categories.id');
        $this->startDate = now()->startOfWeek()->addWeek()->toDateString();
        $this->legalName = (string) $business->legal_name;
        $this->ice = (string) $business->ice;
        $this->legalAddress = (string) $business->legal_address;
    }

    protected function business(): Business
    {
        $business = Business::query()->findOrFail($this->businessId);
        Gate::authorize('manage', $business);

        return $business;
    }

    public function orderPlan(PlaceOrder $order): void
    {
        $this->validate([
            'planId' => ['required', 'integer', Rule::exists('plans', 'id')->where('is_active', true)],
            'period' => ['required', Rule::enum(BillingPeriod::class)],
            'method' => ['required', Rule::in([PaymentMethod::BankTransfer->value, PaymentMethod::CashAgency->value])],
        ]);

        try {
            $order->subscription(Auth::user(), $this->business(), Plan::query()->findOrFail($this->planId), BillingPeriod::from($this->period), PaymentMethod::from($this->method));
        } catch (RateLimited) {
            $this->addError('planId', __('Trop de tentatives. Réessayez plus tard.'));

            return;
        } catch (InvalidArgumentException) {
            $this->addError('planId', __('Cette formule ne peut pas être commandée.'));

            return;
        }

        $this->flash = __('Commande créée. Suivez les instructions de paiement ci-dessous.');
    }

    public function orderPlacement(PlaceOrder $order): void
    {
        $business = $this->business();

        $this->validate([
            'categoryId' => ['required', 'integer', Rule::in($business->categories()->pluck('categories.id')->all())],
            'neighborhoodId' => ['nullable', 'integer', Rule::exists('neighborhoods', 'id')->where('city_id', $business->city_id)],
            'position' => ['required', 'integer', 'between:1,3'],
            'unit' => ['required', Rule::enum(PlacementUnit::class)],
            'units' => ['required', 'integer', 'min:1', 'max:'.($this->unit === 'month' ? PlaceOrder::MAX_PLACEMENT_MONTHS : PlaceOrder::MAX_PLACEMENT_WEEKS)],
            'startDate' => ['required', 'date', 'after_or_equal:today', 'before:'.now()->addMonths(6)->toDateString()],
            'method' => ['required', Rule::in([PaymentMethod::BankTransfer->value, PaymentMethod::CashAgency->value])],
        ]);

        try {
            $order->placement(
                Auth::user(),
                $business,
                Category::query()->findOrFail($this->categoryId),
                $this->neighborhoodId ? Neighborhood::query()->findOrFail($this->neighborhoodId) : null,
                $this->position,
                PlacementUnit::from($this->unit),
                $this->units,
                CarbonImmutable::parse($this->startDate),
                PaymentMethod::from($this->method),
            );
        } catch (SlotUnavailable $e) {
            $this->addError('position', $e->getMessage());

            return;
        } catch (RateLimited) {
            $this->addError('position', __('Trop de tentatives. Réessayez plus tard.'));

            return;
        } catch (InvalidArgumentException) {
            $this->addError('position', __('Commande impossible avec ces paramètres.'));

            return;
        }

        $this->flash = __('Emplacement réservé pendant :hours h. Suivez les instructions de paiement ci-dessous.', ['hours' => config('billing.pending_hold_hours')]);
    }

    public function uploadReceipt(int $paymentId, UploadReceipt $upload): void
    {
        $this->validate([
            "receipts.{$paymentId}" => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:'.config('billing.receipt_max_kb')],
        ], attributes: ["receipts.{$paymentId}" => __('reçu')]);

        $payment = Payment::query()->whereKey($paymentId)->where('business_id', $this->businessId)->firstOrFail();

        try {
            $upload(Auth::user(), $payment, $this->receipts[$paymentId]);
        } catch (InvalidArgumentException) {
            $this->addError("receipts.{$paymentId}", __('Un des fichiers n’a pas pu être lu.'));

            return;
        }

        unset($this->receipts[$paymentId]);
        $this->flash = __('Reçu envoyé. Nous validons votre paiement au plus vite.');
    }

    public function saveLegal(): void
    {
        $this->validate([
            'legalName' => ['nullable', 'string', 'max:190'],
            'ice' => ['nullable', 'digits:15'],
            'legalAddress' => ['nullable', 'string', 'max:255'],
        ]);

        $business = $this->business();
        $business->fill(['legal_name' => $this->legalName ?: null, 'ice' => $this->ice ?: null, 'legal_address' => $this->legalAddress ?: null])->save();
        $this->flash = __('Informations de facturation enregistrées.');
    }

    public function render(PlanFeatures $plans, Pricing $pricing, PlacementInventory $inventory, ManualGateway $manual): View
    {
        $business = $this->business()->load(['city', 'categories']);
        $category = $this->categoryId ? $business->categories->firstWhere('id', $this->categoryId) : null;
        $neighborhood = $this->neighborhoodId ? Neighborhood::query()->where('city_id', $business->city_id)->find($this->neighborhoodId) : null;
        $unit = PlacementUnit::tryFrom($this->unit) ?? PlacementUnit::Week;

        $payments = Payment::query()->where('business_id', $business->id)->with(['invoice', 'media'])->latest()->limit(30)->get();

        return view('livewire.owners.billing', [
            'business' => $business,
            'currentPlan' => $plans->activePlan($business),
            'plans' => Plan::query()->active()->orderBy('sort_order')->get()->reject(fn (Plan $plan) => $plan->isFree()),
            'neighborhoods' => Neighborhood::query()->where('city_id', $business->city_id)->where('is_active', true)->orderBy('slug')->get(),
            'quote' => $category === null ? null : $pricing->placement($business->city, $category, $neighborhood, max(1, min(3, $this->position)), $unit, max(1, $this->units)),
            'grid' => $category === null ? [] : $inventory->weeklyGrid($business->city_id, $category->id, $neighborhood?->id, 8),
            'pending' => $payments->where('status', PaymentStatus::Pending)->values(),
            'history' => $payments->where('status', '!=', PaymentStatus::Pending)->values(),
            'instructions' => fn (Payment $payment) => $manual->instructions($payment),
        ])->title(__('Formules et paiements · :business', ['business' => $business->name]))->layoutData(['noindex' => true]);
    }
}
