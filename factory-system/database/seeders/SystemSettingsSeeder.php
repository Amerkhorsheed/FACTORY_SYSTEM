<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

/**
 * Seeds default system settings.
 * Safe to run multiple times (uses updateOrCreate).
 */
class SystemSettingsSeeder extends Seeder
{
    /** @var array<int, array<string, mixed>> */
    private const SETTINGS = [
        [
            'key' => 'factory_name',
            'value' => 'المعمل النموذجي',
            'type' => 'string',
            'group' => 'factory',
            'label' => 'اسم المعمل',
        ],
        [
            'key' => 'factory_address',
            'value' => 'دمشق، سوريا',
            'type' => 'string',
            'group' => 'factory',
            'label' => 'عنوان المعمل',
        ],
        [
            'key' => 'factory_phone',
            'value' => '011-000-0000',
            'type' => 'string',
            'group' => 'factory',
            'label' => 'هاتف المعمل',
        ],
        [
            'key' => 'factory_tax_number',
            'value' => '',
            'type' => 'string',
            'group' => 'factory',
            'label' => 'الرقم الضريبي',
        ],
        [
            'key' => 'factory_logo',
            'value' => null,
            'type' => 'string',
            'group' => 'factory',
            'label' => 'شعار المعمل',
        ],
        [
            'key' => 'invoice_prefix',
            'value' => 'INV',
            'type' => 'string',
            'group' => 'invoices',
            'label' => 'بادئة رقم الفاتورة',
        ],
        [
            'key' => 'invoice_due_days',
            'value' => '30',
            'type' => 'integer',
            'group' => 'invoices',
            'label' => 'أيام الاستحقاق',
        ],
        [
            'key' => 'invoice_tax_rate',
            'value' => '0',
            'type' => 'integer',
            'group' => 'invoices',
            'label' => 'نسبة الضريبة (%)',
        ],
        [
            'key' => 'invoice_footer_text',
            'value' => 'شكراً لتعاملكم معنا',
            'type' => 'string',
            'group' => 'invoices',
            'label' => 'نص تذييل الفاتورة',
        ],
        [
            'key' => 'invoice_bank_details',
            'value' => '',
            'type' => 'string',
            'group' => 'invoices',
            'label' => 'بيانات البنك',
        ],
        [
            'key' => 'invoice_terms',
            'value' => '',
            'type' => 'string',
            'group' => 'invoices',
            'label' => 'الشروط والأحكام',
        ],
        [
            'key' => 'default_low_threshold',
            'value' => '10',
            'type' => 'integer',
            'group' => 'stock',
            'label' => 'حد المخزون المنخفض الافتراضي',
        ],
        [
            'key' => 'enable_stock_warnings',
            'value' => '1',
            'type' => 'boolean',
            'group' => 'stock',
            'label' => 'تفعيل تحذيرات المخزون',
        ],
        [
            'key' => 'default_credit_limit',
            'value' => '0',
            'type' => 'integer',
            'group' => 'customers',
            'label' => 'الحد الائتماني الافتراضي',
        ],
        [
            'key' => 'default_category',
            'value' => 'B',
            'type' => 'string',
            'group' => 'customers',
            'label' => 'الفئة الافتراضية للعميل',
        ],
        [
            'key' => 'enable_arabic_numerals',
            'value' => '0',
            'type' => 'boolean',
            'group' => 'ui',
            'label' => 'استخدام الأرقام العربية',
        ],
    ];

    public function run(): void
    {
        foreach (self::SETTINGS as $setting) {
            SystemSetting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }

        $this->command->info('System settings seeded ('.count(self::SETTINGS).' settings).');
    }
}
