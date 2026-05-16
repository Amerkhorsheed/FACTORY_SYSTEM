<?php

namespace Tests\Feature;

use App\Models\Driver;
use App\Models\Order;
use App\Models\Shipment;
use App\Models\Truck;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShipmentCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    /** @test */
    public function it_creates_a_shipment_via_controller(): void
    {
        $admin = User::factory()->create()->assignRole('super_admin');
        $truck = Truck::factory()->create(['status' => 'available']);
        $driver = Driver::factory()->create(['is_active' => true]);

        $response = $this->actingAs($admin)
            ->post(route('shipments.store'), [
                'truck_id' => $truck->id,
                'driver_id' => $driver->id,
                'shipment_date' => today()->toDateString(),
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('shipments', [
            'truck_id' => $truck->id,
            'driver_id' => $driver->id,
            'status' => 'planned',
        ]);
    }

    /** @test */
    public function it_dispatches_a_shipment(): void
    {
        $admin = User::factory()->create()->assignRole('super_admin');
        $truck = Truck::factory()->create(['status' => 'available']);
        $driver = Driver::factory()->create(['is_active' => true]);
        $shipment = Shipment::factory()->create([
            'truck_id' => $truck->id,
            'driver_id' => $driver->id,
            'status' => 'planned',
        ]);
        $order = Order::factory()->ready()->create(['shipment_id' => $shipment->id]);

        $response = $this->actingAs($admin)
            ->post(route('shipments.dispatch', $shipment));

        $response->assertRedirect();
        $this->assertSame('dispatched', $shipment->fresh()->status);
        $this->assertSame('shipped', $order->fresh()->status);
        $this->assertSame('on_trip', $truck->fresh()->status);
    }

    /** @test */
    public function it_marks_order_delivered(): void
    {
        $admin = User::factory()->create()->assignRole('super_admin');
        $truck = Truck::factory()->create(['status' => 'available']);
        $driver = Driver::factory()->create(['is_active' => true]);
        $shipment = Shipment::factory()->create([
            'truck_id' => $truck->id,
            'driver_id' => $driver->id,
            'status' => 'dispatched',
        ]);
        $order = Order::factory()->shipped()->create(['shipment_id' => $shipment->id]);

        $response = $this->actingAs($admin)
            ->post(route('shipments.orders.delivered', [$shipment, $order]));

        $response->assertRedirect();
        $this->assertSame('delivered', $order->fresh()->status);
    }

    /** @test */
    public function it_cancels_a_shipment(): void
    {
        $admin = User::factory()->create()->assignRole('super_admin');
        $truck = Truck::factory()->create(['status' => 'available']);
        $driver = Driver::factory()->create(['is_active' => true]);
        $shipment = Shipment::factory()->create([
            'truck_id' => $truck->id,
            'driver_id' => $driver->id,
            'status' => 'planned',
        ]);

        $response = $this->actingAs($admin)
            ->post(route('shipments.status.cancel', $shipment), ['reason' => 'test']);

        $response->assertRedirect();
        $this->assertSame('cancelled', $shipment->fresh()->status);
    }

    /** @test */
    public function it_generates_a_manifest(): void
    {
        $admin = User::factory()->create()->assignRole('super_admin');
        $truck = Truck::factory()->create(['status' => 'available']);
        $driver = Driver::factory()->create(['is_active' => true]);
        $shipment = Shipment::factory()->create([
            'truck_id' => $truck->id,
            'driver_id' => $driver->id,
            'status' => 'planned',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('shipments.manifest', $shipment));

        $response->assertOk();
        $this->assertNotNull($shipment->fresh()->manifest_path);
    }

    /** @test */
    public function it_blocks_unauthorized_shipment_access(): void
    {
        $regularUser = User::factory()->create()->assignRole('customer');
        $truck = Truck::factory()->create();
        $driver = Driver::factory()->create();
        $shipment = Shipment::factory()->create([
            'truck_id' => $truck->id,
            'driver_id' => $driver->id,
        ]);

        $this->actingAs($regularUser)
            ->get(route('shipments.index'))
            ->assertRedirect(route('portal.dashboard'));

        $this->actingAs($regularUser)
            ->get(route('shipments.show', $shipment))
            ->assertRedirect(route('portal.dashboard'));
    }

    /** @test */
    public function it_lists_shipments_with_pagination(): void
    {
        $admin = User::factory()->create()->assignRole('super_admin');
        Shipment::factory(3)->create();

        $this->actingAs($admin)
            ->get(route('shipments.index'))
            ->assertOk();
    }

    /** @test */
    public function it_updates_a_shipment(): void
    {
        $admin = User::factory()->create()->assignRole('super_admin');
        $truck = Truck::factory()->create();
        $driver = Driver::factory()->create();
        $shipment = Shipment::factory()->create([
            'truck_id' => $truck->id,
            'driver_id' => $driver->id,
        ]);
        $newTruck = Truck::factory()->create();
        $newDriver = Driver::factory()->create();

        $this->actingAs($admin)
            ->put(route('shipments.update', $shipment), [
                'truck_id' => $newTruck->id,
                'driver_id' => $newDriver->id,
                'shipment_date' => today()->toDateString(),
            ])
            ->assertRedirect();

        $this->assertSame($newTruck->id, $shipment->fresh()->truck_id);
    }

    /** @test */
    public function it_deletes_a_planned_shipment(): void
    {
        $admin = User::factory()->create()->assignRole('super_admin');
        $shipment = Shipment::factory()->create(['status' => 'planned']);

        $this->actingAs($admin)
            ->delete(route('shipments.destroy', $shipment))
            ->assertRedirect();

        $this->assertSoftDeleted($shipment);
    }
}
