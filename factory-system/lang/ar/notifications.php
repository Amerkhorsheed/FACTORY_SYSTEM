<?php

return [
    'actions' => [
        'mark_all_read' => 'تحديد الكل كمقروء',
        'mark_read' => 'تحديد كمقروء',
        'open_invoice' => 'عرض الفاتورة',
        'open_order' => 'عرض الطلبية',
        'open_product' => 'عرض المخزون',
        'open_report' => 'عرض التقرير',
        'sign_in' => 'تسجيل الدخول',
    ],
    'bell' => [
        'empty' => 'لا توجد إشعارات حالياً',
        'fallback' => 'إشعار جديد',
        'title' => 'الإشعارات',
    ],
    'email' => [
        'auto' => 'هذه رسالة تلقائية، يرجى عدم الرد عليها.',
        'copyright' => 'جميع الحقوق محفوظة.',
    ],
    'invoice_issued' => [
        'body' => 'تم إصدار فاتورة جديدة برقم :number.',
        'message' => 'تم إصدار فاتورة جديدة :number بمبلغ :amount',
        'subject' => 'تم إصدار الفاتورة :number',
        'total' => 'إجمالي الفاتورة',
    ],
    'invoice_overdue' => [
        'body' => 'توجد فواتير مستحقة لم يتم سدادها بعد وتحتاج إلى متابعة.',
        'count' => 'عدد الفواتير المتأخرة',
        'customer' => 'العميل',
        'due_date' => 'تاريخ الاستحقاق',
        'message' => ':count فاتورة متأخرة السداد بإجمالي :amount',
        'number' => 'رقم الفاتورة',
        'subject' => 'تنبيه: :count فواتير متأخرة السداد',
        'total_due' => 'إجمالي المبالغ المتأخرة',
    ],
    'low_stock' => [
        'body' => 'توجد منتجات وصلت إلى حد المخزون المنخفض وتحتاج إلى مراجعة.',
        'current' => 'المخزون الحالي',
        'message' => ':count منتجات وصلت لمستوى المخزون المنخفض',
        'name' => 'المنتج',
        'single_message' => 'المنتج :product وصل إلى :current، وحد التنبيه :threshold',
        'subject' => 'تنبيه مخزون منخفض: :count منتج',
        'threshold' => 'حد التنبيه',
    ],
    'order_status' => [
        'body' => 'تم تحديث حالة طلبك رقم :number.',
        'message' => 'الطلبية :number أصبحت: :status',
        'status' => 'الحالة الجديدة',
        'subject' => 'طلبيتك رقم :number - :status',
    ],
    'payment_received' => [
        'amount' => 'المبلغ المستلم',
        'body' => 'لقد استلمنا دفعتك بنجاح. شكراً لك.',
        'message' => 'تم استلام دفعة بمبلغ :amount على الفاتورة :invoice',
        'subject' => 'تم استلام دفعتك - فاتورة :invoice',
    ],
    'temporary_password' => [
        'body' => 'تم إنشاء كلمة مرور مؤقتة لحسابك.',
        'password' => 'كلمة المرور المؤقتة',
        'subject' => 'كلمة مرور مؤقتة',
        'warning' => 'يرجى تغيير كلمة المرور بعد تسجيل الدخول.',
    ],
];
