<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Creates all roles and permissions.
 * MUST run before AdminUserSeeder.
 * Run order: [1] in DatabaseSeeder.
 */
class RolesAndPermissionsSeeder extends Seeder
{
    /** @var array<int, string> */
    private const PERMISSIONS = [
        // Products
        'products.view',
        'products.create',
        'products.edit',
        'products.delete',
        'products.adjust_stock',
        'products.view_cost_price',
        // Customers
        'customers.view',
        'customers.create',
        'customers.edit',
        'customers.delete',
        'customers.manage_credit',
        'customers.view_balance',
        // Orders
        'orders.view',
        'orders.create',
        'orders.edit',
        'orders.delete',
        'orders.cancel',
        'orders.view_all',
        'orders.assign_shipment',
        'orders.confirm_delivery',
        // Shipments
        'shipments.view',
        'shipments.create',
        'shipments.edit',
        'shipments.dispatch',
        'shipments.update_status',
        'shipments.view_manifest',
        // Invoices
        'invoices.view',
        'invoices.create',
        'invoices.void',
        'invoices.send',
        'invoices.view_all',
        // Payments
        'payments.view',
        'payments.create',
        'payments.delete',
        // ERP
        'erp.expenses.view',
        'erp.expenses.create',
        'erp.expenses.edit',
        'erp.reports.view',
        'erp.reports.export',
        'erp.dashboard.view',
        // System
        'system.users.view',
        'system.users.create',
        'system.users.edit',
        'system.users.delete',
        'system.settings.view',
        'system.settings.edit',
        'system.audit_log.view',
        'system.roles.manage',
    ];

    /**
     * @var array<string, array<int, string>>
     */
    private const ROLE_PERMISSIONS = [
        'accountant' => [
            'products.*',
            'customers.*',
            'orders.*',
            'shipments.*',
            'invoices.*',
            'payments.*',
            'erp.*',
            'system.users.view',
            'system.settings.view',
        ],
        'shipping_staff' => [
            'orders.view',
            'orders.confirm_delivery',
            'shipments.view',
            'shipments.update_status',
            'shipments.view_manifest',
            'invoices.view',
            'products.view',
        ],
        'customer' => [
            'orders.create',
            'orders.view',
            'invoices.view',
        ],
    ];

    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $superAdmin->givePermissionTo(Permission::all());

        foreach (self::ROLE_PERMISSIONS as $roleName => $patterns) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($this->expandPermissions($patterns));
        }

        $this->command->info('Roles and permissions seeded ('.count(self::PERMISSIONS).' permissions, '.(count(self::ROLE_PERMISSIONS) + 1).' roles).');
    }

    /**
     * @param  array<int, string>  $patterns
     * @return array<int, string>
     */
    private function expandPermissions(array $patterns): array
    {
        $resolved = [];

        foreach ($patterns as $pattern) {
            if (str_ends_with($pattern, '.*')) {
                $prefix = str_replace('.*', '.', $pattern);
                $resolved = array_merge(
                    $resolved,
                    array_filter(self::PERMISSIONS, fn ($p) => str_starts_with($p, $prefix))
                );
            } else {
                $resolved[] = $pattern;
            }
        }

        return array_values(array_unique($resolved));
    }
}
