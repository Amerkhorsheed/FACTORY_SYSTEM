# Factory System — Customer Portal v2.0 Implementation Plan

## Executive Summary

Upgrade the customer self-service portal from a basic single-product form to an enterprise-grade, real-time shopping cart experience. This initiative transforms order placement from a static HTML form into a dynamic Livewire-driven interface with visual product catalogs, multi-item cart management, live credit validation, and interactive order tracking. The upgrade directly improves customer satisfaction, reduces order errors, and decreases support load on the factory operations team.

**Scope**: Customer-facing portal (`/portal/*`) order creation and tracking modules.  
**Stack**: Laravel 11, Livewire 3, Alpine.js, Tailwind CSS, existing ERP backend.  
**Timeline**: 3 phases over 6 weeks.  
**Success Criteria**: Cart abandonment < 15%, order placement time < 3 minutes, zero price tampering incidents.

---

## 1. Current State Assessment

### 1.1 Existing Architecture

| Layer | Implementation | Limitation |
|---|---|---|
| **Controller** | `CustomerPortalController::createOrder()` renders static Blade view | No real-time interactivity; full page reload on every action |
| **Form** | Standard HTML `<form>` with single `<select>` dropdown | Single item only; no cart concept; poor UX for 100+ products |
| **Pricing** | Server-side only in `CustomerPortalService::createOrder()` | Customer discovers credit issues only after submission |
| **Validation** | `StorePortalOrderRequest` rules | Reactive only; no proactive UI feedback |
| **Tracking** | Text-based status display in `orders/show.blade.php` | No visual progress indicator |

### 1.2 Code Inventory (Existing)

```
app/Http/Controllers/Customers/CustomerPortalController.php   [99 lines]
app/Services/Customers/CustomerPortalService.php              [99 lines]
app/Repositories/CustomerPortalRepository.php                 [existing]
resources/views/portal/orders/create.blade.php                [35 lines - single item form]
resources/views/portal/orders/show.blade.php                  [existing]
```

### 1.3 Pain Points Identified

1. **Single-item constraint**: Customers cannot add multiple products in one order, forcing multiple submissions.
2. **No price visibility**: Product list shows price, but cart total is invisible until submission.
3. **Credit shock**: Customers only discover insufficient credit after form submission, causing frustration.
4. **Poor product discovery**: Dropdown menu fails with >50 products; no search, filter, or category grouping.
5. **Static tracking**: Order status is plain text; no visual timeline or estimated delivery indicators.

---

## 2. Target State Architecture

### 2.1 High-Level Design

```
┌─────────────────────────────────────────────────────────────────┐
│  Customer Browser                                                │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────────────┐   │
│  │ Product Grid │  │  Live Cart   │  │   Credit Meter       │   │
│  │ (Alpine.js)  │  │  (Livewire)  │  │  (Real-time Bar)     │   │
│  └──────────────┘  └──────────────┘  └──────────────────────┘   │
└────────────────────┬────────────────────────────────────────────┘
                     │ AJAX / Livewire
┌────────────────────▼────────────────────────────────────────────┐
│  Laravel Backend                                                 │
│  ┌──────────────────┐  ┌──────────────────┐  ┌──────────────┐   │
│  │ OrderCart        │  │ CustomerPortal   │  │ OrderService │   │
│  │ Livewire         │  │ Service (v2)     │  │ (existing)   │   │
│  │ Component        │  │                  │  │              │   │
│  └──────────────────┘  └──────────────────┘  └──────────────┘   │
│  ┌──────────────────┐  ┌──────────────────┐                     │
│  │ ProductSearch    │  │ CreditValidator  │                     │
│  │ Action           │  │ Service          │                     │
│  └──────────────────┘  └──────────────────┘                     │
└─────────────────────────────────────────────────────────────────┘
```

### 2.2 Component Specification

#### A. Livewire Component: `OrderCart`

**File**: `app/Livewire/Portal/OrderCart.php`

| Property | Type | Purpose |
|---|---|---|
| `$items` | `array` | Cart items: `[['product_id' => int, 'quantity' => int, 'unit_price' => int, 'name' => string]]` |
| `$searchQuery` | `string` | Product search input bound to grid filter |
| `$selectedCategory` | `?int` | Active category filter |
| `$notes` | `?string` | Order-level notes |
| `$requestedDeliveryDate` | `?string` | Delivery date input |
| `$customer` | `Customer` | Authenticated customer's model |

**Methods**:
- `mount(Customer $customer)`: Initialize cart, load customer credit data.
- `addProduct(int $productId)`: Add product to cart array; validate stock availability.
- `removeProduct(int $index)`: Remove item by array index.
- `updateQuantity(int $index, int $quantity)`: Adjust quantity; recalculate totals.
- `calculateTotals()`: Compute subtotal, tax, discount, grand total using `OrderFinancialsService`.
- `getAvailableCreditProperty()`: Compute `$customer->credit_limit - $customer->outstanding_balance - $cartTotal`.
- `checkout()`: Validate credit, validate stock, create order via `CustomerPortalService::createOrder`, redirect to tracking.

**Events Dispatched**:
- `productAdded`: Toast notification "Added to cart".
- `creditExceeded`: Warning banner when cart total > available credit.
- `orderCreated`: Redirect to `portal.orders.show` with success message.

#### B. UI Layer: `order-cart.blade.php`

**Layout**: Two-column responsive grid (Tailwind `lg:grid-cols-3`)

**Left Panel (2/3 width) — Product Discovery**:
- **Search Bar**: Real-time filter with 300ms debounce (Alpine.js `x-model.debounce`).
- **Category Pills**: Horizontal scrollable chip list; click to filter.
- **Product Cards**: Grid of cards (`sm:grid-cols-2 lg:grid-cols-3`) showing:
  - Product image (if available)
  - Product name (Arabic, bold)
  - Unit price (formatted via `Money::format()`)
  - Stock availability badge (green/orange/red)
  - "Add to Cart" button with quantity stepper

**Right Panel (1/3 width) — Sticky Cart**:
- **Credit Meter**: Visual progress bar showing `used / limit` credit.
- **Cart Items List**: Scrollable list with product name, qty, line total, delete icon.
- **Order Summary**: Subtotal, tax, discount, grand total (all integer math).
- **Delivery Date**: Datepicker input.
- **Notes**: Textarea for special instructions.
- **Submit Button**: Disabled state when credit exceeded or cart empty.

**Animations** (Tailwind + Alpine.js):
- Product card hover: `scale-105 shadow-lg` transition.
- Cart item add: `transition-all duration-300` slide-in.
- Credit bar: Smooth width transition on value change.
- Toast notifications: Slide-down from top with auto-dismiss.

#### C. Backend Service: `CustomerPortalService` (v2 Enhancements)

**File**: `app/Services/Customers/CustomerPortalService.php` (modified)

**New Methods**:
- `searchProducts(string $query, ?int $categoryId): Collection`: Filter active products by name/code with optional category scope.
- `getProductById(int $id): Product`: Eager-load product for cart injection.
- `validateCartCredit(Customer $customer, int $cartTotal): bool`: Compare against available credit.
- `createOrder(Customer $customer, array $data, User $actor): Order`: Enhanced with transaction safety and event dispatch.

**Security Hardening**:
- All prices are fetched server-side from `Product::unit_price`; frontend prices are display-only.
- Cart submission re-validates every product ID and quantity against database.
- Credit check is performed both in Livewire (UI feedback) and service (final gate).

#### D. Order Tracking: Visual Timeline

**File**: `resources/views/portal/orders/tracking.blade.php`

**Design**: Vertical stepper component showing order lifecycle:

```
[Icon] Pending        ── Active (blue)
[Icon] Accepted       ── Completed (green) + timestamp
[Icon] Preparing     ── Upcoming (gray)
[Icon] Ready          ── Upcoming (gray)
[Icon] Shipped       ── Upcoming (gray) + truck icon
[Icon] Delivered     ── Upcoming (gray)
```

**Features**:
- Current status highlighted with primary color pulse animation.
- Completed steps show checkmark icons with `accepted_at`, `shipped_at`, `delivered_at` timestamps.
- If `cancelled`, show red termination state with `cancel_reason`.
- Estimated delivery date calculation based on `shipment_date` + average delivery window.

---

## 3. Data Flow & State Management

### 3.1 Cart Lifecycle

```
1. Customer opens /portal/orders/create
   └─> Livewire mounts: loads customer, available credit, product catalog

2. Customer searches "cement"
   └─> Alpine.js debounces input (300ms)
   └─> Calls Livewire action searchProducts('cement')
   └─> Returns filtered Collection
   └─> Alpine updates grid without full page reload

3. Customer clicks "Add" on product ID 42
   └─> Livewire addProduct(42)
   └─> Fetches product unit_price from DB (security)
   └─> Appends to $items array
   └─> calculateTotals() recalculates cart value
   └─> getAvailableCreditProperty updates credit meter
   └─> If cart > credit: emits creditExceeded event
   └─> UI shows warning banner, disables checkout

4. Customer adjusts quantity to 5
   └─> Livewire updateQuantity(index, 5)
   └─> Validates stock_quantity >= 5
   └─> Recalculates line total
   └─> Updates cart summary in real-time

5. Customer clicks "Submit Order"
   └─> Livewire checkout()
   └─> Final validation: credit, stock, product existence
   └─> Calls CustomerPortalService::createOrder()
   └─> DB transaction: create Order + OrderItems
   └─> Fire OrderCreated event
   └─> Listener: notify admins via database notification
   └─> Redirect to /portal/orders/{id} with success toast
```

### 3.2 State Synchronization Strategy

| State | Owner | Sync Method | Rationale |
|---|---|---|---|
| Product catalog | Livewire | Server-side query | Prices must never come from client |
| Cart items | Livewire | `$items` array | Central source of truth for totals |
| Search filter | Alpine.js | `x-model.debounce` | Reduces server round-trips |
| Category filter | Alpine.js | `x-model` | Client-side toggle, no server call |
| UI animations | Alpine.js | `x-transition` | Smooth without Livewire latency |
| Credit calculation | Livewire | Computed property | Must use server-side math |

---

## 4. Implementation Phases

### Phase 1: Foundation & Architecture (Week 1-2)

**Deliverables**:
- [ ] Create `app/Livewire/Portal/OrderCart.php` with state management
- [ ] Create `resources/views/livewire/portal/order-cart.blade.php` shell
- [ ] Create `app/Livewire/Portal/ProductSearch.php` action component
- [ ] Update `CustomerPortalController::createOrder()` to render Livewire component
- [ ] Update `CustomerPortalService` with `searchProducts()`, `validateCartCredit()`
- [ ] Write feature tests: cart state, product search, credit validation

**Acceptance Criteria**:
- Component mounts without errors.
- Product search returns filtered results in < 200ms.
- Credit validation blocks checkout when exceeded.

### Phase 2: Premium UI/UX (Week 3-4)

**Deliverables**:
- [ ] Implement product card grid with images, badges, and hover effects
- [ ] Implement sticky cart sidebar with credit meter visualization
- [ ] Add quantity steppers (+/- buttons) with stock validation
- [ ] Add toast notifications for add/remove actions
- [ ] Add empty-state illustration for empty cart
- [ ] Implement responsive mobile layout (stacked columns)
- [ ] Write UI regression tests (render states)

**Acceptance Criteria**:
- Visual design matches factory branding (primary color, RTL layout).
- Mobile experience is fully functional (touch-friendly targets ≥ 44px).
- Animations run at 60fps on mid-range devices.

### Phase 3: Tracking & Polish (Week 5-6)

**Deliverables**:
- [ ] Create visual timeline component for `orders/show.blade.php`
- [ ] Add `OrderPlacedByCustomer` event and `NotifyAdminsOfNewPortalOrder` listener
- [ ] Add estimated delivery calculation logic
- [ ] Implement print-friendly order summary view
- [ ] Performance optimization: product list pagination (50 per page)
- [ ] Accessibility audit: ARIA labels, keyboard navigation, focus management
- [ ] Final integration tests and user acceptance testing

**Acceptance Criteria**:
- Timeline accurately reflects order status history.
- Admin notifications fire within 5 seconds of order creation.
- Lighthouse accessibility score ≥ 90.
- All existing tests continue to pass (180 tests baseline).

---

## 5. Security & Compliance

### 5.1 Threat Model

| Threat | Mitigation |
|---|---|
| **Price tampering** | All prices fetched server-side by product ID; frontend prices are display-only annotations |
| **Credit limit bypass** | Validation in both Livewire (UI) and Service (final gate); race condition protected by DB transaction |
| **Stock oversell** | Stock validated at add-to-cart and checkout; uses existing `ValidateStockAvailabilityPipe` |
| **Cross-customer data** | `CustomerPortalMiddleware` ensures `auth()->user()->customer_id` matches all queries |
| **CSRF** | Livewire handles CSRF automatically; manual forms use `@csrf` |

### 5.2 Authorization Matrix

| Action | Policy | Condition |
|---|---|---|
| View portal | `CustomerPolicy::viewPortal` | `user->customer_id == customer->id` |
| Create order | `OrderPolicy::create` | `user->hasPermissionTo('orders.create')` |
| View own order | `OrderPolicy::view` | `user->customer_id == order->customer_id` |
| View own invoice | `InvoicePolicy::view` | `user->customer_id == invoice->customer_id` |

---

## 6. Testing Strategy

### 6.1 Automated Test Coverage

**Feature Tests** (`tests/Feature/PortalOrderCartTest.php`):
- `it_displays_product_catalog_with_search_and_filters()`
- `it_adds_products_to_cart_and_calculates_totals()`
- `it_blocks_checkout_when_credit_limit_exceeded()`
- `it_blocks_checkout_when_stock_unavailable()`
- `it_creates_order_with_multiple_items_via_livewire()`
- `it_dispatches_admin_notification_on_portal_order()`
- `it_shows_visual_timeline_for_order_tracking()`

**Unit Tests** (`tests/Unit/Portal/CreditValidatorTest.php`):
- `it_calculates_available_credit_correctly()`
- `it_detects_credit_exceeded_boundary()`

**Browser Tests** (if Dusk configured):
- `test_customer_can_place_multi_item_order()`
- `test_credit_meter_updates_in_realtime()`

### 6.2 Manual Verification Checklist

- [ ] Login as customer → verify dashboard metrics accuracy
- [ ] Search product by partial Arabic name → verify results
- [ ] Add 3 products → verify cart totals match manual calculation
- [ ] Increase quantity to exceed stock → verify warning appears
- [ ] Add items to exceed credit → verify checkout disables
- [ ] Submit valid order → verify redirect to tracking page
- [ ] Login as admin → verify notification received
- [ ] View order tracking → verify timeline matches status
- [ ] Test on mobile device → verify responsive layout

---

## 7. Risk Assessment

| Risk | Probability | Impact | Mitigation |
|---|---|---|---|
| Livewire latency on slow networks | Medium | Medium | Alpine.js handles search/filter client-side; only cart mutations hit server |
| Large product catalogs (>500) slow render | Low | High | Implement pagination (50/page) + virtual scrolling |
| Customer confusion with new UI | Medium | Low | Add onboarding tooltips; keep old flow available for 2 weeks |
| Browser compatibility issues | Low | Medium | Test on Chrome, Firefox, Safari, Edge; use Tailwind defaults |
| Concurrent order exceeds credit | Low | High | DB transaction + atomic credit check in `OrderService::create()` |

---

## 8. Success Metrics

| Metric | Baseline | Target | Measurement |
|---|---|---|---|
| Average order placement time | 5+ minutes (multiple forms) | < 3 minutes | Analytics logging on checkout completion |
| Cart abandonment rate | N/A (no cart) | < 15% | Track `create` page exits without `store` |
| Order errors (credit/stock) | 8-12% of submissions | < 2% | Monitor exception logs + support tickets |
| Customer satisfaction | Unknown | > 4.2/5 | Post-order survey (Phase 3) |
| Admin notification latency | N/A | < 5 seconds | Queue worker processing time |
| Mobile order percentage | Unknown | > 40% | User-agent analytics |

---

## 9. File Inventory (Target State)

```
[NEW] app/Livewire/Portal/OrderCart.php
[NEW] app/Livewire/Portal/ProductSearch.php
[NEW] app/Events/Orders/OrderPlacedByCustomer.php
[NEW] app/Listeners/NotifyAdminsOfNewPortalOrder.php
[NEW] app/Services/Portal/CreditValidator.php
[MOD] app/Services/Customers/CustomerPortalService.php
[MOD] app/Http/Controllers/Customers/CustomerPortalController.php
[MOD] resources/views/portal/orders/create.blade.php  [now renders <livewire:portal.order-cart />]
[NEW] resources/views/livewire/portal/order-cart.blade.php
[NEW] resources/views/portal/orders/tracking.blade.php
[NEW] resources/views/components/portal/credit-meter.blade.php
[NEW] resources/views/components/portal/product-card.blade.php
[NEW] resources/views/components/portal/timeline-step.blade.php
[NEW] tests/Feature/PortalOrderCartTest.php
[NEW] tests/Unit/Portal/CreditValidatorTest.php
```

---

## 10. Non-Functional Requirements

- **Performance**: Initial page load < 1.5s; product search < 200ms; cart updates < 100ms.
- **Accessibility**: WCAG 2.1 AA compliant; keyboard-navigable; screen-reader friendly.
- **RTL**: All layouts mirror correctly for Arabic text; numeric inputs remain LTR.
- **Responsive**: Breakpoints at `sm:640px`, `md:768px`, `lg:1024px`; mobile-first approach.
- **Browser Support**: Last 2 versions of Chrome, Firefox, Safari, Edge.

---

*This plan is a living document. Update version and changelog when scope changes.*
