<?php

return [
    'role' => ['member' => 'Member', 'business_owner' => 'Business owner', 'moderator' => 'Moderator', 'admin' => 'Administrator'],
    'business_status' => ['pending' => 'Pending', 'published' => 'Published', 'suspended' => 'Suspended'],
    'review_status' => ['pending' => 'Pending', 'published' => 'Published', 'rejected' => 'Rejected', 'hidden_by_moderation' => 'Hidden by moderation'],
    'reply_status' => ['pending' => 'Pending', 'published' => 'Published', 'rejected' => 'Rejected', 'hidden_by_moderation' => 'Hidden by moderation'],
    'report_reason' => [
        'spam' => 'Spam or advertising', 'fake' => 'Fake review or listing', 'offensive' => 'Offensive language',
        'conflict_of_interest' => 'Conflict of interest', 'wrong_information' => 'Wrong information',
        'closed_business' => 'Business closed', 'inappropriate_photo' => 'Inappropriate photo', 'other' => 'Other',
    ],
    'report_status' => ['open' => 'Open', 'resolved' => 'Resolved', 'dismissed' => 'Dismissed'],
    'claim_method' => ['phone_otp_to_business_number' => 'Code sent to the business number', 'document_upload' => 'Document (trade register, patente, ID)', 'manual' => 'Manual check'],
    'claim_status' => ['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'],
    'edit_suggestion_status' => ['pending' => 'Pending', 'approved' => 'Applied', 'rejected' => 'Rejected'],
    'billing_period' => ['monthly' => 'Monthly', 'yearly' => 'Yearly'],
    'subscription_status' => ['pending' => 'Awaiting payment', 'active' => 'Active', 'expired' => 'Expired', 'cancelled' => 'Cancelled'],
    'placement_status' => ['pending' => 'Awaiting payment', 'active' => 'Active', 'expired' => 'Expired', 'cancelled' => 'Cancelled'],
    'payment_method' => ['bank_transfer' => 'Bank transfer', 'cash_agency' => 'Cash at an agency (CashPlus, Wafacash)', 'cmi' => 'Card (CMI)', 'manual' => 'Manual'],
    'payment_status' => ['pending' => 'Pending', 'validated' => 'Validated', 'rejected' => 'Rejected', 'refunded' => 'Refunded'],
    'contact_event_type' => ['profile_view' => 'Profile view', 'call_click' => 'Call', 'whatsapp_click' => 'WhatsApp', 'directions_click' => 'Directions', 'website_click' => 'Website'],
    'contact_event_source' => ['organic' => 'Organic', 'sponsored' => 'Sponsored', 'search' => 'Search', 'map' => 'Map'],
    'otp_purpose' => ['login' => 'Login', 'business_claim' => 'Business claim', 'phone_verification' => 'Phone verification'],
    'search_sort' => ['relevance' => 'Relevance', 'rating' => 'Best rated', 'distance' => 'Nearest', 'reviews' => 'Most reviewed', 'newest' => 'Newest'],
    'payment_purpose' => ['subscription' => 'Subscription', 'placement' => 'Sponsored placement'],
    'placement_unit' => ['week' => 'Week', 'month' => 'Month'],
];
