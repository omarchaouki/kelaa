<?php

namespace App\Filament\Resources\Businesses\Schemas;

use App\Filament\Support\TranslatableFields;
use App\Models\Business;
use App\Models\Category;
use App\Models\City;
use App\Models\Neighborhood;
use App\Rules\MoroccanPhoneNumber;
use App\Support\PriceRange;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Unique;

class BusinessForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('Identité'))->columns(2)->columnSpanFull()->schema([
                TextInput::make('name')->label(__('Nom'))->required()->maxLength(120),
                TextInput::make('slug')
                    ->label(__('Slug (URL)'))
                    ->required()
                    ->alphaDash()
                    ->maxLength(140)
                    ->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule, Get $get) => $rule->where('city_id', $get('city_id')))
                    ->dehydrateStateUsing(fn (string $state) => Str::slug($state)),
                Select::make('city_id')->label(__('Ville'))
                    ->options(fn () => City::query()->orderBy('slug')->get()->mapWithKeys(fn (City $city) => [$city->id => $city->name]))
                    ->required()->live(),
                Select::make('neighborhood_id')->label(__('Quartier'))
                    ->options(fn (Get $get) => Neighborhood::query()->where('city_id', $get('city_id'))->orderBy('slug')->get()
                        ->mapWithKeys(fn (Neighborhood $n) => [$n->id => $n->name]))
                    ->searchable(),
                Select::make('category_ids')->label(__('Catégories'))
                    ->multiple()
                    ->options(fn () => self::categoryOptions())
                    ->required()
                    ->live()
                    ->dehydrated(false),
                Select::make('primary_category_id')->label(__('Catégorie principale'))
                    ->options(fn (Get $get) => array_intersect_key(self::categoryOptions(), array_flip(array_map('intval', (array) $get('category_ids')))))
                    ->required()
                    ->in(fn (Get $get) => array_map('intval', (array) $get('category_ids')))
                    ->dehydrated(false),
                Toggle::make('is_verified')->label(__('Vérifié'))->helperText(__('Identité ou documents contrôlés par l’équipe.')),
            ]),
            TranslatableFields::make('description', __('Description'), fn (string $path) => Textarea::make($path)->rows(3)->maxLength(2000), required: false)
                ->columnSpanFull(),
            Section::make(__('Contact et localisation'))->columns(2)->columnSpanFull()->schema([
                TextInput::make('phone')->label(__('Téléphone'))->rule(MoroccanPhoneNumber::any())->maxLength(20),
                TextInput::make('whatsapp')->label('WhatsApp')->rule(MoroccanPhoneNumber::mobile())->maxLength(20),
                TextInput::make('email')->label(__('E-mail'))->email()->maxLength(190),
                TextInput::make('website')->label(__('Site web'))->url()->maxLength(255),
                TextInput::make('address')->label(__('Adresse'))->maxLength(255)->columnSpanFull(),
                TextInput::make('latitude')->label(__('Latitude'))->numeric()->required()->minValue(-90)->maxValue(90),
                TextInput::make('longitude')->label(__('Longitude'))->numeric()->required()->minValue(-180)->maxValue(180),
                Select::make('price_range')->label(__('Prix'))->options(collect(range(1, 4))->mapWithKeys(fn (int $level) => [$level => PriceRange::label($level)])),
                Toggle::make('is_always_open')->label(__('Ouvert 24h/24, 7j/7')),
            ]),
        ]);
    }

    /**
     * @return array<int, string>
     */
    public static function categoryOptions(): array
    {
        return Category::query()->whereNotNull('parent_id')->with('parent')->orderBy('slug')->get()
            ->mapWithKeys(fn (Category $c) => [$c->id => $c->parent?->name.' › '.$c->name])
            ->all();
    }

    /**
     * Keeps the pivot in sync: exactly one primary category, which must be selected.
     *
     * @param  Business  $business
     * @param  array<int|string>  $categoryIds
     */
    public static function syncCategories($business, array $categoryIds, int $primaryId): void
    {
        $ids = array_values(array_unique(array_map('intval', $categoryIds)));

        if (! in_array($primaryId, $ids, true)) {
            $ids[] = $primaryId;
        }

        $business->categories()->sync(collect($ids)->mapWithKeys(fn (int $id) => [$id => ['is_primary' => $id === $primaryId]])->all());
        $business->searchable();
    }
}
