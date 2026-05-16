<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create shipments table.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('shipments', function (Blueprint $table): void {
            $table->id();
            $table->string('shipment_number', 30)->unique();
            $table->foreignId('truck_id')->constrained('trucks')->restrictOnDelete();
            $table->foreignId('driver_id')->constrained('drivers')->restrictOnDelete();
            $table->date('shipment_date');
            $table->enum('status', ['planned', 'loading', 'dispatched', 'completed', 'cancelled'])->default('planned');
            $table->timestamp('departure_time')->nullable();
            $table->timestamp('return_time')->nullable();
            $table->string('manifest_path')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['shipment_date', 'status']);
            $table->index(['truck_id', 'status']);
            $table->index(['driver_id', 'shipment_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
