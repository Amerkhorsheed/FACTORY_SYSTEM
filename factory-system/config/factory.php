<?php

/**
 * Factory System - Business Configuration.
 */
return [
    'name' => env('FACTORY_NAME', 'المعمل'),
    'currency' => env('FACTORY_CURRENCY', 'SYP'),
    'tax_rate' => env('FACTORY_TAX_RATE', 0),

    'code_prefixes' => [
        'order' => 'ORD',
        'invoice' => 'INV',
        'shipment' => 'SHP',
        'payment' => 'PAY',
        'customer' => 'CUS',
        'product' => 'PRD',
    ],

    'order_statuses' => [
        'pending' => 'معلقة',
        'accepted' => 'مقبولة',
        'preparing' => 'قيد التجهيز',
        'ready' => 'جاهزة للشحن',
        'shipped' => 'مشحونة',
        'delivered' => 'مسلّمة',
        'cancelled' => 'ملغاة',
        'returned' => 'مرتجعة',
    ],

    'invoice_statuses' => [
        'draft' => 'مسودة',
        'issued' => 'صادرة',
        'sent' => 'مرسلة',
        'paid' => 'مدفوعة',
        'partial' => 'مدفوعة جزئياً',
        'void' => 'ملغاة',
    ],

    'shipment_statuses' => [
        'planned' => 'مخطط لها',
        'loading' => 'قيد التحميل',
        'dispatched' => 'في الطريق',
        'completed' => 'مكتملة',
        'cancelled' => 'ملغاة',
    ],

    'truck_statuses' => [
        'available' => 'متاحة',
        'on_trip' => 'في الطريق',
        'maintenance' => 'صيانة',
        'inactive' => 'غير نشطة',
    ],

    'payment_methods' => [
        'cash' => 'نقداً',
        'credit' => 'آجل',
        'check' => 'شيك',
        'bank_transfer' => 'تحويل بنكي',
    ],

    'customer_categories' => [
        'A' => ['label' => 'فئة A', 'description' => 'عملاء مميزون'],
        'B' => ['label' => 'فئة B', 'description' => 'عملاء عاديون'],
        'C' => ['label' => 'فئة C', 'description' => 'عملاء جدد'],
    ],

    'pagination' => [
        'per_page' => 20,
    ],

    'stock' => [
        'default_low_threshold' => 10,
    ],

    'session' => [
        'lifetime_minutes' => 120,
    ],
];
