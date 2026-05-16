<?php

namespace App\Services\Customers;

use App\Contracts\Repositories\CustomerRepositoryInterface;
use App\Contracts\Services\CustomerServiceInterface;
use App\DTOs\Customers\CreateCustomerDTO;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\User;
use App\Services\BaseService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Hash;

/**
 * Customer management service.
 * Handles CRUD, portal access, and balance recalculation.
 */
class CustomerService extends BaseService implements CustomerServiceInterface
{
    public function __construct(
        private readonly CustomerRepositoryInterface $customers,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function list(array $filters, int $perPage = 0): LengthAwarePaginator
    {
        return $this->customers->paginateWithFilters(
            $filters,
            $perPage ?: config('factory.pagination.per_page', 20)
        );
    }

    /**
     * Create a new customer. Optionally creates a portal user account.
     *
     * @throws \Throwable
     */
    public function create(CreateCustomerDTO $dto): Customer
    {
        return $this->transaction(function () use ($dto) {
            $customer = $this->customers->create([
                'name' => $dto->name,
                'business_name' => $dto->businessName,
                'phone' => $dto->phone,
                'phone_alt' => $dto->phoneAlt,
                'email' => $dto->email,
                'address' => $dto->address,
                'city' => $dto->city,
                'region' => $dto->region,
                'category' => $dto->category,
                'credit_limit' => $dto->creditLimit,
                'notes' => $dto->notes,
                'is_active' => true,
                'portal_access' => $dto->portalAccess,
                'created_by' => $dto->createdBy,
            ]);

            if ($dto->portalAccess && $dto->portalPassword && $dto->email) {
                $this->createPortalUser($customer, $dto->portalPassword);
            }

            return $customer;
        });
    }

    /**
     * Update customer details.
     *
     * @throws \Throwable
     */
    public function update(Customer $customer, CreateCustomerDTO $dto): Customer
    {
        return $this->transaction(function () use ($customer, $dto) {
            return $this->customers->update($customer, [
                'name' => $dto->name,
                'business_name' => $dto->businessName,
                'phone' => $dto->phone,
                'phone_alt' => $dto->phoneAlt,
                'email' => $dto->email,
                'address' => $dto->address,
                'city' => $dto->city,
                'region' => $dto->region,
                'category' => $dto->category,
                'credit_limit' => $dto->creditLimit,
                'notes' => $dto->notes,
            ]);
        });
    }

    /**
     * Soft-delete a customer — guarded by HasSoftDeleteGuard on the model.
     *
     * @throws \DomainException if customer has active orders
     * @throws \Throwable
     */
    public function delete(Customer $customer): void
    {
        $this->customers->delete($customer);
    }

    /**
     * Enable portal access for a customer: create linked User with 'customer' role.
     *
     * @throws \DomainException if no email is set
     * @throws \Throwable
     */
    public function enablePortalAccess(Customer $customer, string $password): User
    {
        if (! $customer->email) {
            throw new \DomainException(__('customers.portal_requires_email'));
        }

        return $this->transaction(function () use ($customer, $password) {
            $user = $this->createPortalUser($customer, $password);
            $this->customers->update($customer, ['portal_access' => true]);

            return $user;
        });
    }

    /**
     * Disable portal access: deactivate the linked user account.
     *
     * @throws \Throwable
     */
    public function disablePortalAccess(Customer $customer): void
    {
        $this->transaction(function () use ($customer) {
            if ($customer->user) {
                $customer->user->update(['is_active' => false]);
            }
            $this->customers->update($customer, ['portal_access' => false]);
        });
    }

    /**
     * Recalculate outstanding balance from live invoice data.
     */
    public function recalculateBalance(Customer $customer): void
    {
        $outstanding = Invoice::where('customer_id', $customer->id)
            ->whereNotIn('status', ['paid', 'void'])
            ->sum('balance_due');

        $this->customers->update($customer, ['outstanding_balance' => (int) $outstanding]);
    }

    /**
     * Get customer's recent order history.
     */
    public function getOrderHistory(Customer $customer): Collection
    {
        return $customer->orders()
            ->with(['items.product', 'invoice'])
            ->latest('order_date')
            ->limit(100)
            ->get();
    }

    /** @return array<string, int> */
    public function getKpis(): array
    {
        return [
            'total' => Customer::count(),
            'category_a' => Customer::where('category', 'A')->count(),
            'with_debt' => Customer::where('outstanding_balance', '>', 0)->count(),
        ];
    }

    public function loadDetails(Customer $customer): Customer
    {
        return $customer->load([
            'orders' => fn ($q) => $q->latest()->limit(10),
            'invoices',
        ]);
    }

    // ── Private helpers ───────────────────────────────────────────────────

    private function createPortalUser(Customer $customer, string $password): User
    {
        $user = User::create([
            'name' => $customer->name,
            'email' => $customer->email,
            'phone' => $customer->phone,
            'password' => Hash::make($password),
            'is_active' => true,
        ]);

        $user->assignRole('customer');
        $this->customers->update($customer, ['user_id' => $user->id]);

        return $user;
    }
}
