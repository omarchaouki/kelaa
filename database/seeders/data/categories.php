<?php

/*
 * Initial category tree: [group => children]. Each entry: slug, fr, ar, en, icon (Lucide name),
 * optional schema.org type for JSON-LD. Children of an emergency group are emergency categories.
 *
 * Transport > "Stations de taxis" only lists taxi stations and licensed transport
 * companies, never individual drivers offering private rides (moderation rule).
 */

return [
    [
        'slug' => 'urgences', 'fr' => 'Urgences', 'ar' => 'مستعجلات', 'en' => 'Emergencies', 'icon' => 'siren', 'emergency' => true,
        'children' => [
            ['slug' => 'pharmacie-de-garde', 'fr' => 'Pharmacie de garde', 'ar' => 'صيدلية الحراسة', 'en' => 'On-duty pharmacy', 'icon' => 'pill', 'schema' => 'Pharmacy'],
            ['slug' => 'medecin', 'fr' => 'Médecin', 'ar' => 'طبيب', 'en' => 'Doctor', 'icon' => 'stethoscope', 'schema' => 'Physician'],
            ['slug' => 'clinique', 'fr' => 'Clinique', 'ar' => 'مصحة', 'en' => 'Clinic', 'icon' => 'hospital', 'schema' => 'MedicalClinic'],
            ['slug' => 'depannage-auto', 'fr' => 'Dépannage auto', 'ar' => 'إغاثة السيارات', 'en' => 'Car breakdown service', 'icon' => 'truck', 'schema' => 'AutoRepair'],
            ['slug' => 'serrurier', 'fr' => 'Serrurier', 'ar' => 'صانع الأقفال', 'en' => 'Locksmith', 'icon' => 'key-round', 'schema' => 'Locksmith'],
        ],
    ],
    [
        'slug' => 'maison', 'fr' => 'Maison', 'ar' => 'المنزل', 'en' => 'Home', 'icon' => 'house',
        'children' => [
            ['slug' => 'plombier', 'fr' => 'Plombier', 'ar' => 'سباك', 'en' => 'Plumber', 'icon' => 'wrench', 'schema' => 'Plumber'],
            ['slug' => 'electricien', 'fr' => 'Électricien', 'ar' => 'كهربائي', 'en' => 'Electrician', 'icon' => 'zap', 'schema' => 'Electrician'],
            ['slug' => 'peintre', 'fr' => 'Peintre', 'ar' => 'صباغ', 'en' => 'Painter', 'icon' => 'paint-roller', 'schema' => 'HousePainter'],
            ['slug' => 'menuisier', 'fr' => 'Menuisier', 'ar' => 'نجار', 'en' => 'Carpenter', 'icon' => 'hammer', 'schema' => 'GeneralContractor'],
            ['slug' => 'climatisation', 'fr' => 'Climatisation', 'ar' => 'التكييف', 'en' => 'Air conditioning', 'icon' => 'air-vent', 'schema' => 'HVACBusiness'],
            ['slug' => 'femme-de-menage', 'fr' => 'Femme de ménage', 'ar' => 'عاملة نظافة', 'en' => 'Housekeeper', 'icon' => 'sparkles', 'schema' => 'HomeAndConstructionBusiness'],
            ['slug' => 'demenagement', 'fr' => 'Déménagement', 'ar' => 'نقل الأثاث', 'en' => 'Movers', 'icon' => 'package', 'schema' => 'MovingCompany'],
            ['slug' => 'jardinier', 'fr' => 'Jardinier', 'ar' => 'بستاني', 'en' => 'Gardener', 'icon' => 'flower-2', 'schema' => 'HomeAndConstructionBusiness'],
            ['slug' => 'vitrier', 'fr' => 'Vitrier', 'ar' => 'زجاج', 'en' => 'Glazier', 'icon' => 'app-window', 'schema' => 'HomeAndConstructionBusiness'],
            ['slug' => 'reparation-electromenager', 'fr' => 'Réparation électroménager', 'ar' => 'إصلاح الأجهزة المنزلية', 'en' => 'Appliance repair', 'icon' => 'washing-machine', 'schema' => 'HomeAndConstructionBusiness'],
        ],
    ],
    [
        'slug' => 'auto-et-moto', 'fr' => 'Auto et moto', 'ar' => 'السيارات والدراجات', 'en' => 'Cars and motorbikes', 'icon' => 'car',
        'children' => [
            ['slug' => 'mecanicien', 'fr' => 'Mécanicien', 'ar' => 'ميكانيكي', 'en' => 'Mechanic', 'icon' => 'cog', 'schema' => 'AutoRepair'],
            ['slug' => 'electricien-auto', 'fr' => 'Électricien auto', 'ar' => 'كهربائي السيارات', 'en' => 'Auto electrician', 'icon' => 'battery-charging', 'schema' => 'AutoRepair'],
            ['slug' => 'vulcanisation-pneus', 'fr' => 'Vulcanisation / pneus', 'ar' => 'فولكانيزاسيون / العجلات', 'en' => 'Tyres', 'icon' => 'circle-dot', 'schema' => 'TireShop'],
            ['slug' => 'lavage-auto', 'fr' => 'Lavage auto', 'ar' => 'غسل السيارات', 'en' => 'Car wash', 'icon' => 'droplets', 'schema' => 'AutoWash'],
            ['slug' => 'carrosserie', 'fr' => 'Carrosserie', 'ar' => 'تصليح هياكل السيارات', 'en' => 'Body shop', 'icon' => 'car-front', 'schema' => 'AutoBodyShop'],
            ['slug' => 'pieces-detachees', 'fr' => 'Pièces détachées', 'ar' => 'قطع الغيار', 'en' => 'Spare parts', 'icon' => 'settings', 'schema' => 'AutoPartsStore'],
        ],
    ],
    [
        'slug' => 'transport', 'fr' => 'Transport', 'ar' => 'النقل', 'en' => 'Transport', 'icon' => 'bus',
        'children' => [
            ['slug' => 'stations-de-taxis', 'fr' => 'Stations de taxis', 'ar' => 'محطات سيارات الأجرة', 'en' => 'Taxi stations', 'icon' => 'car-taxi-front', 'schema' => 'TaxiStand'],
            ['slug' => 'location-de-voitures', 'fr' => 'Location de voitures', 'ar' => 'كراء السيارات', 'en' => 'Car rental', 'icon' => 'key', 'schema' => 'AutoRental'],
            ['slug' => 'transport-de-marchandises', 'fr' => 'Transport de marchandises', 'ar' => 'نقل البضائع', 'en' => 'Freight transport', 'icon' => 'truck', 'schema' => 'LocalBusiness'],
            ['slug' => 'transport-scolaire', 'fr' => 'Transport scolaire', 'ar' => 'النقل المدرسي', 'en' => 'School transport', 'icon' => 'bus', 'schema' => 'LocalBusiness'],
        ],
    ],
    [
        'slug' => 'livraison', 'fr' => 'Livraison', 'ar' => 'التوصيل', 'en' => 'Delivery', 'icon' => 'bike',
        'children' => [
            ['slug' => 'livreurs-independants', 'fr' => 'Livreurs indépendants', 'ar' => 'موصلون مستقلون', 'en' => 'Independent couriers', 'icon' => 'bike', 'schema' => 'LocalBusiness'],
            ['slug' => 'coursiers', 'fr' => 'Coursiers', 'ar' => 'سعاة', 'en' => 'Errand runners', 'icon' => 'send', 'schema' => 'LocalBusiness'],
        ],
    ],
    [
        'slug' => 'food', 'fr' => 'Food', 'ar' => 'الأكل', 'en' => 'Food', 'icon' => 'utensils',
        'children' => [
            ['slug' => 'restaurants', 'fr' => 'Restaurants', 'ar' => 'مطاعم', 'en' => 'Restaurants', 'icon' => 'utensils', 'schema' => 'Restaurant'],
            ['slug' => 'snacks', 'fr' => 'Snacks', 'ar' => 'سناك', 'en' => 'Snack bars', 'icon' => 'sandwich', 'schema' => 'FastFoodRestaurant'],
            ['slug' => 'cafes', 'fr' => 'Cafés', 'ar' => 'مقاهي', 'en' => 'Cafés', 'icon' => 'coffee', 'schema' => 'CafeOrCoffeeShop'],
            ['slug' => 'boulangeries', 'fr' => 'Boulangeries', 'ar' => 'مخابز', 'en' => 'Bakeries', 'icon' => 'croissant', 'schema' => 'Bakery'],
            ['slug' => 'patisseries', 'fr' => 'Pâtisseries', 'ar' => 'حلويات', 'en' => 'Pastry shops', 'icon' => 'cake-slice', 'schema' => 'Bakery'],
            ['slug' => 'traiteurs', 'fr' => 'Traiteurs', 'ar' => 'ممونون', 'en' => 'Caterers', 'icon' => 'chef-hat', 'schema' => 'FoodEstablishment'],
        ],
    ],
    [
        'slug' => 'beaute-et-bien-etre', 'fr' => 'Beauté et bien-être', 'ar' => 'التجميل والعناية', 'en' => 'Beauty and wellness', 'icon' => 'scissors',
        'children' => [
            ['slug' => 'coiffeur-hommes', 'fr' => 'Coiffeur hommes', 'ar' => 'حلاق رجالي', 'en' => "Men's barber", 'icon' => 'scissors', 'schema' => 'HairSalon'],
            ['slug' => 'coiffeuse', 'fr' => 'Coiffeuse', 'ar' => 'حلاقة نسائية', 'en' => "Women's hairdresser", 'icon' => 'scissors', 'schema' => 'HairSalon'],
            ['slug' => 'hammam', 'fr' => 'Hammam', 'ar' => 'حمام', 'en' => 'Hammam', 'icon' => 'bath', 'schema' => 'DaySpa'],
            ['slug' => 'estheticienne', 'fr' => 'Esthéticienne', 'ar' => 'تجميل', 'en' => 'Beautician', 'icon' => 'sparkles', 'schema' => 'BeautySalon'],
        ],
    ],
    [
        'slug' => 'sante', 'fr' => 'Santé', 'ar' => 'الصحة', 'en' => 'Health', 'icon' => 'heart-pulse',
        'children' => [
            ['slug' => 'dentiste', 'fr' => 'Dentiste', 'ar' => 'طبيب أسنان', 'en' => 'Dentist', 'icon' => 'smile', 'schema' => 'Dentist'],
            ['slug' => 'opticien', 'fr' => 'Opticien', 'ar' => 'نظاراتي', 'en' => 'Optician', 'icon' => 'glasses', 'schema' => 'Optician'],
            ['slug' => 'kinesitherapeute', 'fr' => 'Kinésithérapeute', 'ar' => 'مروض طبي', 'en' => 'Physiotherapist', 'icon' => 'activity', 'schema' => 'Physiotherapy'],
            ['slug' => 'laboratoire', 'fr' => 'Laboratoire', 'ar' => 'مختبر تحاليل', 'en' => 'Medical laboratory', 'icon' => 'flask-conical', 'schema' => 'DiagnosticLab'],
            ['slug' => 'veterinaire', 'fr' => 'Vétérinaire', 'ar' => 'طبيب بيطري', 'en' => 'Veterinarian', 'icon' => 'paw-print', 'schema' => 'VeterinaryCare'],
        ],
    ],
    [
        'slug' => 'services', 'fr' => 'Services', 'ar' => 'خدمات', 'en' => 'Services', 'icon' => 'briefcase',
        'children' => [
            ['slug' => 'couturier', 'fr' => 'Couturier', 'ar' => 'خياط', 'en' => 'Tailor', 'icon' => 'shirt', 'schema' => 'LocalBusiness'],
            ['slug' => 'cordonnier', 'fr' => 'Cordonnier', 'ar' => 'إسكافي', 'en' => 'Cobbler', 'icon' => 'footprints', 'schema' => 'LocalBusiness'],
            ['slug' => 'reparation-telephone', 'fr' => 'Réparation téléphone', 'ar' => 'إصلاح الهواتف', 'en' => 'Phone repair', 'icon' => 'smartphone', 'schema' => 'ElectronicsStore'],
            ['slug' => 'informatique', 'fr' => 'Informatique', 'ar' => 'الإعلاميات', 'en' => 'Computer services', 'icon' => 'laptop', 'schema' => 'ComputerStore'],
            ['slug' => 'imprimerie-photocopie', 'fr' => 'Imprimerie / photocopie', 'ar' => 'مطبعة / نسخ', 'en' => 'Printing / photocopy', 'icon' => 'printer', 'schema' => 'LocalBusiness'],
            ['slug' => 'cours-particuliers', 'fr' => 'Cours particuliers', 'ar' => 'دروس خصوصية', 'en' => 'Private tutoring', 'icon' => 'graduation-cap', 'schema' => 'EducationalOrganization'],
            ['slug' => 'serrurerie', 'fr' => 'Serrurerie', 'ar' => 'حدادة', 'en' => 'Metalwork', 'icon' => 'lock', 'schema' => 'HomeAndConstructionBusiness'],
            ['slug' => 'pressing', 'fr' => 'Pressing', 'ar' => 'مصبنة', 'en' => 'Dry cleaning', 'icon' => 'shirt', 'schema' => 'DryCleaningOrLaundry'],
        ],
    ],
];
