<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

/**
 * Creates the legal/static pages once, in French, English and Arabic
 * (data/legal.php), then edited in the admin panel. Existing pages are
 * never overwritten.
 */
class PageSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            'cgu' => ['fr' => "Conditions générales d'utilisation", 'ar' => 'شروط الاستخدام', 'en' => 'Terms of use'],
            'confidentialite' => ['fr' => 'Politique de confidentialité', 'ar' => 'سياسة الخصوصية', 'en' => 'Privacy policy'],
            'regles-des-avis' => ['fr' => 'Règles des avis', 'ar' => 'قواعد التقييمات', 'en' => 'Review guidelines'],
            'cookies' => ['fr' => 'Politique cookies', 'ar' => 'سياسة ملفات تعريف الارتباط', 'en' => 'Cookie policy'],
            'mentions-legales' => ['fr' => 'Mentions légales', 'ar' => 'إشعار قانوني', 'en' => 'Legal notice'],
            'a-propos' => ['fr' => 'À propos', 'ar' => 'من نحن', 'en' => 'About'],
            'contact' => ['fr' => 'Contact', 'ar' => 'اتصل بنا', 'en' => 'Contact'],
        ];

        /** @var array<string, array{fr: string, en: string, ar: string}> $bodies */
        $bodies = require __DIR__.'/data/legal.php';

        foreach ($pages as $slug => $title) {
            Page::query()->firstOrCreate(['slug' => $slug], [
                'title' => $title,
                'body' => $bodies[$slug],
                'is_published' => true,
            ]);
        }
    }
}
