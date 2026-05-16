<?php

namespace App\DTOs\Customers;

/**
 * Immutable data transfer object for customer creation and update.
 */
final class CreateCustomerDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $phone,
        public readonly string $address,
        public readonly string $category,
        public readonly int $createdBy,
        public readonly ?string $businessName = null,
        public readonly ?string $phoneAlt = null,
        public readonly ?string $email = null,
        public readonly ?string $city = null,
        public readonly ?string $region = null,
        public readonly int $creditLimit = 0,
        public readonly ?string $notes = null,
        public readonly bool $portalAccess = false,
        public readonly ?string $portalPassword = null,
    ) {}

    /**
     * Build from validated request array.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            phone: $data['phone'],
            address: $data['address'],
            category: $data['category'] ?? 'B',
            createdBy: (int) ($data['created_by'] ?? auth()->id()),
            businessName: $data['business_name'] ?? null,
            phoneAlt: $data['phone_alt'] ?? null,
            email: $data['email'] ?? null,
            city: $data['city'] ?? null,
            region: $data['region'] ?? null,
            creditLimit: (int) ($data['credit_limit'] ?? 0),
            notes: $data['notes'] ?? null,
            portalAccess: (bool) ($data['portal_access'] ?? false),
            portalPassword: $data['portal_password'] ?? null,
        );
    }
}
