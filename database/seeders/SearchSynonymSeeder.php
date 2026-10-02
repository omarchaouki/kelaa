<?php

namespace Database\Seeders;

use App\Models\SearchSynonym;
use Illuminate\Database\Seeder;

/**
 * Starter synonym groups: French, Arabic and Darija as people type it
 * (Latin letters with 3/7/9 for ع/ح/ق, common misspellings). The list is
 * maintained from the admin panel afterwards; existing terms are kept.
 */
class SearchSynonymSeeder extends Seeder
{
    public function run(): void
    {
        $groups = [
            // Health and emergencies
            'pharmacie' => ['صيدلية', 'farmasyan', 'pharmacien', 'فرمسيان', 'pharma', 'saydaliya'],
            'médecin' => ['طبيب', 'tbib', 'docteur', 'doctor', 'دكتور', 'medcin', 'toubib'],
            'dentiste' => ['طبيب الأسنان', 'tbib snan', 'tbib dyal snan', 'dentist', 'سنان'],
            'pédiatre' => ['طبيب الأطفال', 'tbib drari', 'pediatre'],
            'gynécologue' => ['طبيبة النساء', 'gyneco', 'tbiba'],
            'ambulance' => ['إسعاف', 'lambulance', 'سيارة الإسعاف', 'is3af'],
            'clinique' => ['مصحة', 'klinik', 'clinic', 'سبيطار'],
            'hôpital' => ['مستشفى', 'sbitar', 'سبيطار', 'hopital'],
            'laboratoire' => ['مختبر', 'labo', 'tahalil', 'تحاليل', 'analyse'],
            'radiologie' => ['راديو', 'radio', 'scanner', 'echographie', 'إيكوغرافي'],
            'vétérinaire' => ['بيطري', 'bitri', 'veto', 'tbib l7iwanat'],

            // Food
            'restaurant' => ['مطعم', 'resto', 'ristora', 'restau', 'ماكلة', 'makla'],
            'snack' => ['سناك', 'snak', 'tacos', 'sandwich', 'كاسكروط', 'kaskrout'],
            'pizzeria' => ['pizza', 'بيتزا', 'fast food', 'fastfood'],
            'tanjia' => ['طنجية', 'mechoui', 'مشوي', 'grillade', 'chwa', 'شوا'],
            'poulet' => ['دجاج', 'djaj', 'روتيسري', 'rotisserie', 'poulet roti'],
            'café' => ['قهوة', 'qahwa', '9ahwa', 'cafe', 'مقهى'],
            'pâtisserie' => ['حلويات', 'patisserie', 'gâteaux', 'halwa', '7alawiyat', 'gato'],
            'boulangerie' => ['مخبزة', 'khobz', 'خبز', 'boulanjri', 'pain'],
            'ferran' => ['فران', 'four traditionnel', 'farnatchi', 'فرناتشي'],
            'traiteur' => ['ممون', 'traiteur mariage', 'ممون الحفلات'],

            // Groceries
            'épicerie' => ['حانوت', 'hanout', '7anout', 'moul l7anout', 'بقال', 'epicerie'],
            'supermarché' => ['supermarche', 'marjane', 'carrefour', 'bim', 'aswak'],
            'boucherie' => ['جزار', 'gzzar', 'gazar', 'lham', 'لحم', 'boucher'],
            'poissonnerie' => ['حوت', '7out', 'hout', 'سمك', 'poisson'],
            'fruits et légumes' => ['خضرة', 'khodra', 'خضار', 'legumes', 'fakiha'],
            'crèmerie' => ['حليب', '7lib', 'lben', 'لبن', 'raib', 'رايب'],
            'huile d’olive' => ['زيت الزيتون', 'zit zitoun', 'zit l3oud', 'huile olive'],
            'gaz' => ['بوطة', 'bouta', 'gaz butane', 'قنينة الغاز'],
            'attar' => ['عطار', '3attar', 'epices', 'توابل', 'herboriste'],

            // Home and repairs
            'plombier' => ['سباك', 'plombi', 'plombié', 'lplombi', 'بلومبي'],
            'électricien' => ['تريسيان', 'trisyan', 'electricien', 'كهربائي', 'trisian'],
            'menuisier' => ['نجار', 'njjar', 'nejjar', 'menuiserie'],
            'aluminium' => ['ألمنيوم', 'alu', 'menuiserie aluminium', 'aluminyom'],
            'ferronnerie' => ['حداد', '7dad', 'haddad', 'soudure', 'لحام', 'forgeron'],
            'peintre' => ['صباغ', 'sebbagh', 'sebagh', 'peinture'],
            'maçon' => ['بناء', 'bennay', 'macon', 'معلم البني'],
            'carreleur' => ['زلايجي', 'zellige', 'zlayji', 'carrelage'],
            'plâtrier' => ['جباس', 'gebs', 'jbas', 'gyps', 'platre'],
            'climatisation' => ['كليماتيزور', 'climatiseur', 'clim', 'chauffe-eau', 'شوفو'],
            'parabole' => ['برابول', 'parabol', 'demo', 'antenne'],
            'serrurier' => ['صانع الأقفال', 'serruri', 'clés', 'ouverture porte'],
            'déménagement' => ['رحيل', 'r7il', 'demenagement', 'نقل الأثاث'],

            // Cars and transport
            'mécanicien' => ['ميكانيكي', 'mikanisyan', 'mecanicien', 'mikanik', 'ميكانيك', 'garage'],
            'électricien auto' => ['تريسيان طوموبيل', 'trisyan tomobil', 'electricite auto'],
            'tôlerie' => ['طولي', 'tolier', 'carrosserie', 'طوليري'],
            'vulcanisation' => ['فولكانيزاسيون', 'vulca', 'pneu', 'pneus', 'roues', 'رويضة'],
            'lavage' => ['غسيل', 'lavaj', 'lavage auto', 'ghsil tomobil'],
            'dépannage' => ['ديباناج', 'depanage', 'remorquage', 'dépanneuse'],
            'location voiture' => ['كراء السيارات', 'kra tomobil', 'location auto', 'location de voiture', 'car rental', 'kira'],
            'auto-école' => ['أوطو إيكول', 'auto ecole', 'permis', 'bermi', 'برمي'],
            'taxi' => ['طاكسي', 'grand taxi', 'petit taxi', 'طاكسي كبير'],
            'autocar' => ['كار', 'kar', 'ctm', 'supratours', 'bus'],
            'station-service' => ['محطة البنزين', 'essence', 'gasoil', 'mazout', 'لاكار'],

            // Weddings and events
            'salle des fêtes' => ['قاعة الحفلات', 'sala', 'salle', 'قاعة'],
            'neggafa' => ['نكافة', 'nekafa', 'negafa', 'العروسة'],
            'orchestre' => ['جوق', 'dakka', 'دقة', 'chaabi', 'شعبي', 'aissawa'],
            'tente' => ['خيمة', 'khayma', 'chaises', 'كراسي', 'location tente'],
            'photographe' => ['مصور', 'msawer', 'photo', 'video', 'كاميرا'],

            // Beauty
            'coiffeur' => ['حلاق', 'hellaq', '7ellaq', 'coiffeuse', 'barber'],
            'hammam' => ['حمام', 'hamam', '7mam'],
            'henné' => ['حناء', 'nekacha', 'نقاشة', '7enna', 'henna'],

            // Paperwork and money
            'transfert d’argent' => ['تحويل', 'wafacash', 'cashplus', 'barid cash', 'western union', 'hawala', 'حوالة'],
            'banque' => ['بنك', 'bank', 'guichet', 'gab', 'atm'],
            'assurance' => ['تأمين', 'assurance auto', 'ta2min', 'لاسورانس'],
            'adoul' => ['عدول', '3doul', 'adel', 'عدل', 'zwaj', 'عقد الزواج'],
            'notaire' => ['موثق', 'notaire', 'mowatiq'],
            'avocat' => ['محامي', 'mo7ami', 'mohami', 'avocate'],
            'écrivain public' => ['الكاتب العمومي', 'kateb', 'katib', 'كاتب'],
            'agence de voyage' => ['وكالة الأسفار', 'omra', 'عمرة', 'hajj', 'حج'],
            'immobilier' => ['سمسار', 'samsar', 'كراء الدار', 'location appartement', 'kra dar'],
            'comptable' => ['محاسب', 'fiduciaire', 'comptabilité', 'mo7asib'],

            // Local services and shopping
            'téléphone' => ['تليفون', 'portable', 'tilifoun', 'smartphone', 'réparation téléphone'],
            'informatique' => ['pc', 'ordinateur', 'حاسوب', 'laptop'],
            'photocopie' => ['نسخ', 'photocopy', 'tirage', 'impression'],
            'couturier' => ['خياط', 'khiyat', 'kheyat', 'retouche', 'tailleur'],
            'cordonnier' => ['خراز', 'kharraz', 'cordonie'],
            'pressing' => ['مصبنة', 'masbana', 'blanchisserie', 'laverie'],
            'quincaillerie' => ['دروغري', 'droguerie', 'quincaillerie', 'ferraille'],
            'matériaux' => ['مواد البناء', 'ciment', 'سيمة', 'sima', 'brique'],
            'librairie' => ['مكتبة', 'maktaba', 'papeterie', 'fournitures scolaires'],

            // Farming
            'moulin à huile' => ['معصرة', 'maasra', 'm3asra', 'presse olive'],
            'tracteur' => ['تراكتور', 'traktor', 'tracteur', 'moissonneuse', 'حصادة'],
            'puits' => ['بئر', 'bir', 'forage', 'sondage', 'حفر الآبار'],
            'pompage solaire' => ['الطاقة الشمسية', 'panneau solaire', 'plaque solaire', 'pompe'],
            'aliment bétail' => ['علف', '3alf', 'aliment', 'نخالة', 'chwal'],
            'engrais' => ['سماد', 'smad', 'دواء الفلاحة', 'pesticide'],

            // Sport and leisure
            'salle de sport' => ['gym', 'musculation', 'fitness', 'سالة', 'sala dyal sport'],
            'mini-foot' => ['terrain', 'تيران', 'foot', 'ملعب'],
            'hôtel' => ['فندق', 'otel', 'hotel', 'funduq'],
        ];

        foreach ($groups as $term => $synonyms) {
            SearchSynonym::query()->firstOrCreate(['term' => $term, 'locale' => null], ['synonyms' => $synonyms]);
        }
    }
}
