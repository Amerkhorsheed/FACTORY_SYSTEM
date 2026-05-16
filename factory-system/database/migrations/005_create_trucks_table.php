<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create delivery trucks table.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('trucks', function (Blueprint $table): void {
            $table->id();
            $table->string('plate_number', 30)->unique();
            $table->string('model', 100)->nullable();
            $table->decimal('capacity_kg', 10, 2)->nullable();
            $table->unsignedInteger('capacity_units')->nullable();
            $table->enum('status', ['available', 'on_trip', 'maintenance', 'inactive'])->default('available');
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trucks');
    }
};
