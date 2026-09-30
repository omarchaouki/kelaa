<?php

namespace Database\Seeders;

use App\Models\SearchSynonym;
use Illuminate\Database\Seeder;

/**
 * Starter synonym groups (French / Arabic / Darija in Latin letters).
 * The list is maintained from the admin panel afterwards.
 */
class SearchSynonymSeeder extends Seeder
{
    public function run(): void
    {
        $groups = [
            'plombier' => ['سباك', 'plombi', 'plombié', 'lplombi', 'بلومبي'],
            'mécanicien' => ['ميكانيكي', 'mikanisyan', 'mecanicien', 'mikanik', 'ميكانيك'],
            'électricien' => ['تريسيان', 'trisyan', 'electricien', 'كهربائي', 'trisian'],
            'pharmacie' => ['صيدلية', 'farmasyan', 'pharmacien', 'فرمسيان'],
            'menuisier' => ['نجار', 'njjar', 'nejjar'],
            'peintre' => ['صباغ', 'sebbagh', 'sebagh'],
            'coiffeur' => ['حلاق', 'hellaq', 'coiffeuse', 'barber'],
            'serrurier' => ['صانع الأقفال', 'serruri', 'clés'],
            'dépannage' => ['ديباناج', 'depanage', 'remorquage'],
            'vulcanisation' => ['فولكانيزاسيون', 'vulca', 'pneu', 'pneus'],
            'taxi' => ['طاكسي', 'grand taxi', 'petit taxi'],
            'hammam' => ['حمام', 'hamam'],
            'pâtisserie' => ['حلويات', 'patisserie', 'gâteaux'],
        ];

        foreach ($groups as $term => $synonyms) {
            SearchSynonym::query()->firstOrCreate(['term' => $term, 'locale' => null], ['synonyms' => $synonyms]);
        }
    }
}
