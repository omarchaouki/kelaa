<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

/**
 * Creates empty legal/static pages once. Their content is written in the
 * admin panel (legal texts must be reviewed by a lawyer before launch).
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

        $placeholder = [
            'fr' => '<p>Contenu à compléter depuis le panneau d’administration.</p>',
            'ar' => '<p>المحتوى قيد الإعداد.</p>',
            'en' => '<p>Content to be written from the admin panel.</p>',
        ];

        foreach ($pages as $slug => $title) {
            Page::query()->firstOrCreate(['slug' => $slug], [
                'title' => $title,
                'body' => $placeholder,
                'is_published' => true,
            ]);
        }
    }
}
