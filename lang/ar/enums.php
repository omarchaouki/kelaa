<?php

return [
    'role' => ['member' => 'عضو', 'business_owner' => 'مهني', 'moderator' => 'مشرف', 'admin' => 'مدير'],
    'business_status' => ['pending' => 'قيد المراجعة', 'published' => 'منشور', 'suspended' => 'موقوف'],
    'review_status' => ['pending' => 'قيد المراجعة', 'published' => 'منشور', 'rejected' => 'مرفوض', 'hidden_by_moderation' => 'مخفي من طرف الإشراف'],
    'reply_status' => ['pending' => 'قيد المراجعة', 'published' => 'منشور', 'rejected' => 'مرفوض', 'hidden_by_moderation' => 'مخفي من طرف الإشراف'],
    'report_reason' => [
        'spam' => 'رسائل مزعجة أو إشهار', 'fake' => 'تقييم أو صفحة مزيفة', 'offensive' => 'كلام مسيء',
        'conflict_of_interest' => 'تضارب المصالح', 'wrong_information' => 'معلومات خاطئة',
        'closed_business' => 'المحل مغلق', 'inappropriate_photo' => 'صورة غير لائقة', 'other' => 'أخرى',
    ],
    'report_status' => ['open' => 'مفتوح', 'resolved' => 'تمت المعالجة', 'dismissed' => 'مرفوض'],
    'claim_method' => ['phone_otp_to_business_number' => 'رمز مرسل إلى رقم المحل', 'document_upload' => 'وثيقة (السجل التجاري، الباتانتا، البطاقة الوطنية)', 'manual' => 'تحقق يدوي'],
    'claim_status' => ['pending' => 'قيد المراجعة', 'approved' => 'مقبولة', 'rejected' => 'مرفوضة'],
    'edit_suggestion_status' => ['pending' => 'قيد المراجعة', 'approved' => 'مطبق', 'rejected' => 'مرفوض'],
    'billing_period' => ['monthly' => 'شهري', 'yearly' => 'سنوي'],
    'subscription_status' => ['pending' => 'في انتظار الأداء', 'active' => 'نشط', 'expired' => 'منتهي', 'cancelled' => 'ملغى'],
    'placement_status' => ['pending' => 'في انتظار الأداء', 'active' => 'نشط', 'expired' => 'منتهي', 'cancelled' => 'ملغى'],
    'payment_method' => ['bank_transfer' => 'تحويل بنكي', 'cash_agency' => 'نقدا في وكالة (كاش بلوس، وفاكاش)', 'cmi' => 'بطاقة بنكية (CMI)', 'manual' => 'يدوي'],
    'payment_status' => ['pending' => 'قيد المراجعة', 'validated' => 'مؤكد', 'rejected' => 'مرفوض', 'refunded' => 'مسترجع'],
    'contact_event_type' => ['profile_view' => 'مشاهدة الصفحة', 'call_click' => 'اتصال', 'whatsapp_click' => 'واتساب', 'directions_click' => 'الاتجاهات', 'website_click' => 'الموقع الإلكتروني'],
    'contact_event_source' => ['organic' => 'طبيعي', 'sponsored' => 'مُموَّل', 'search' => 'بحث', 'map' => 'خريطة'],
    'otp_purpose' => ['login' => 'تسجيل الدخول', 'business_claim' => 'المطالبة بالمحل', 'phone_verification' => 'التحقق من الهاتف', 'account_deletion' => 'حذف الحساب'],
    'search_sort' => ['relevance' => 'الأكثر صلة', 'rating' => 'الأعلى تقييما', 'distance' => 'الأقرب', 'reviews' => 'الأكثر تقييمات', 'newest' => 'الأحدث'],
    'payment_purpose' => ['subscription' => 'اشتراك', 'placement' => 'مكان مُموَّل'],
    'placement_unit' => ['week' => 'أسبوع', 'month' => 'شهر'],
];
