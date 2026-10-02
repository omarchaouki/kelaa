<?php

namespace App\Support\Seo;

use App\Models\Business;
use App\Models\BusinessHour;
use App\Models\Category;
use App\Models\City;

/**
 * schema.org structured data. Only public listing data goes in here, and the
 * output is always encoded with JsonLd::encode() so user text cannot close
 * the <script> element.
 */
class JsonLd
{
    /**
     * schema.org types a category may use (anything else falls back to LocalBusiness).
     */
    public const TYPES = [
        'LocalBusiness', 'Pharmacy', 'Dentist', 'Physician', 'MedicalClinic', 'Hospital', 'Optician',
        'Restaurant', 'CafeOrCoffeeShop', 'Bakery', 'FastFoodRestaurant', 'GroceryStore', 'Store',
        'AutoRepair', 'AutoPartsStore', 'GasStation', 'AutoWash', 'HairSalon', 'BeautySalon', 'DaySpa',
        'HealthClub', 'Plumber', 'Electrician', 'Locksmith', 'HomeAndConstructionBusiness',
        'HousePainter', 'RoofingContractor', 'GeneralContractor', 'MovingCompany', 'LegalService',
        'Attorney', 'Notary', 'AccountingService', 'FinancialService', 'RealEstateAgent',
        'TravelAgency', 'LodgingBusiness', 'Hotel', 'ChildCare', 'School', 'EducationalOrganization',
        'VeterinaryCare', 'DryCleaningOrLaundry', 'ComputerStore', 'ElectronicsStore',
        'ClothingStore', 'HardwareStore', 'FurnitureStore', 'Florist', 'SportsActivityLocation',
        'EmergencyService', 'GovernmentOffice', 'PostOffice', 'Library', 'TaxiService', 'TaxiStand',
        'HVACBusiness', 'TireShop', 'AutoBodyShop', 'AutoRental', 'FoodEstablishment', 'Physiotherapy', 'DiagnosticLab',
        'IceCreamShop', 'AutoDealer', 'ShoeStore', 'JewelryStore', 'MobilePhoneStore', 'HomeGoodsStore', 'BookStore',
        'EventVenue', 'BankOrCreditUnion', 'InsuranceAgency', 'ExerciseGym', 'PublicSwimmingPool',
        'CollegeOrUniversity', 'ProfessionalService',
    ];

    private const DAYS = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'];

    /**
     * Expects categories, hours, city and media to be loaded.
     *
     * @return array<string, mixed>
     */
    public static function localBusiness(Business $business, ?Category $primaryCategory, string $url): array
    {
        $data = [
            '@context' => 'https://schema.org',
            '@type' => self::typeFor($primaryCategory),
            '@id' => $url.'#business',
            'name' => $business->name,
            'url' => $url,
            'address' => array_filter([
                '@type' => 'PostalAddress',
                'streetAddress' => $business->address,
                'addressLocality' => $business->city->name,
                'addressCountry' => 'MA',
            ]),
            'geo' => [
                '@type' => 'GeoCoordinates',
                'latitude' => round($business->latitude, 6),
                'longitude' => round($business->longitude, 6),
            ],
        ];

        if ($business->description) {
            $data['description'] = str($business->description)->squish()->limit(300)->toString();
        }

        if ($business->phone) {
            $data['telephone'] = $business->phone;
        }

        if ($business->price_range) {
            $data['priceRange'] = str_repeat('$', $business->price_range);
        }

        $images = collect([$business->getFirstMediaUrl('cover', 'large')])
            ->merge($business->getMedia('gallery')->take(3)->map(fn ($media) => $media->getUrl('large')))
            ->filter()
            ->values()
            ->all();

        if ($images !== []) {
            $data['image'] = $images;
        }

        if ($hours = self::openingHours($business)) {
            $data['openingHoursSpecification'] = $hours;
        }

        if ($business->rating_count > 0) {
            $data['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => round($business->rating_avg, 1),
                'reviewCount' => $business->rating_count,
                'bestRating' => 5,
                'worstRating' => 1,
            ];
        }

        return $data;
    }

    /**
     * @param  list<array{name: string, url: string}>  $items
     * @return array<string, mixed>
     */
    public static function breadcrumbs(array $items): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => array_map(fn (array $item, int $index) => [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $item['name'],
                'item' => $item['url'],
            ], $items, array_keys($items)),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function website(City $city, string $homeUrl, string $searchUrl): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => config('app.name').' · '.$city->name,
            'url' => $homeUrl,
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => $searchUrl.'?q={search_term_string}',
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }

    public static function typeFor(?Category $category): string
    {
        foreach ([$category?->schema_type, $category?->parent?->schema_type] as $type) {
            if ($type !== null && in_array($type, self::TYPES, true)) {
                return $type;
            }
        }

        return 'LocalBusiness';
    }

    /**
     * Safe to print inside <script type="application/ld+json">: "<", ">" and "&"
     * are escaped, so "</script>" in a business name stays inert.
     *
     * @param  array<string, mixed>  $data
     */
    public static function encode(array $data): string
    {
        return (string) json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function openingHours(Business $business): array
    {
        if ($business->is_always_open) {
            return [[
                '@type' => 'OpeningHoursSpecification',
                'dayOfWeek' => array_values(self::DAYS),
                'opens' => '00:00',
                'closes' => '23:59',
            ]];
        }

        return $business->hours
            ->reject(fn (BusinessHour $hour) => $hour->is_closed || $hour->opens_at === null || $hour->closes_at === null)
            ->sortBy(['day_of_week', 'opens_at'])
            ->map(fn (BusinessHour $hour) => [
                '@type' => 'OpeningHoursSpecification',
                'dayOfWeek' => self::DAYS[$hour->day_of_week],
                'opens' => substr((string) $hour->opens_at, 0, 5),
                'closes' => substr((string) $hour->closes_at, 0, 5),
            ])
            ->values()
            ->all();
    }
}
