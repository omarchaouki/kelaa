<?php

namespace App\Filament\Widgets;

use App\Enums\BusinessStatus;
use App\Enums\ClaimStatus;
use App\Enums\PaymentStatus;
use App\Enums\Permission;
use App\Enums\ReportStatus;
use App\Enums\ReviewStatus;
use App\Models\Business;
use App\Models\BusinessClaim;
use App\Models\Payment;
use App\Models\Report;
use App\Models\Review;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class ModerationOverview extends StatsOverviewWidget
{
    protected static ?int $sort = -1;

    protected ?string $pollingInterval = '60s';

    /**
     * @return array<Stat>
     */
    protected function getStats(): array
    {
        $stats = [
            Stat::make(__('Fiches à valider'), Business::query()->where('status', BusinessStatus::Pending)->count()),
            Stat::make(__('Avis en attente'), Review::query()->where('status', ReviewStatus::Pending)->count()),
            Stat::make(__('Revendications en attente'), BusinessClaim::query()->where('status', ClaimStatus::Pending)->count()),
            Stat::make(__('Signalements ouverts'), Report::query()->where('status', ReportStatus::Open)->count()),
            Stat::make(__('Nouvelles fiches (7 jours)'), Business::query()->where('created_at', '>=', now()->subWeek())->count()),
        ];

        // Money is for admins only.
        if (auth()->user()?->can(Permission::ManagePayments->value)) {
            $stats[] = Stat::make(__('Paiements à valider'), Payment::query()->where('status', PaymentStatus::Pending)->count());
            $stats[] = Stat::make(__('Revenus du mois'), Number::currency(
                (float) Payment::query()
                    ->where('status', PaymentStatus::Validated)
                    ->where('validated_at', '>=', now()->startOfMonth())
                    ->sum('amount'),
                'MAD',
                'fr',
            ));
        }

        return $stats;
    }
}
