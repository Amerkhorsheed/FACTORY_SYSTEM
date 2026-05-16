<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create products inventory table.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('product_categories')->restrictOnDelete();
            $table->string('code', 50)->unique();
            $table->string('name', 200);
            $table->text('description')->nullable();
            $table->string('unit', 30);
            $table->unsignedBigInteger('unit_price')->default(0);
            $table->unsignedBigInteger('cost_price')->default(0);
            $table->string('barcode', 100)->nullable()->unique();
            $table->string('image')->nullable();
            $table->integer('stock_quantity')->default(0);
            $table->unsignedInteger('low_stock_threshold')->default(10);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['category_id', 'is_active', 'sort_order']);
            $table->index(['stock_quantity', 'low_stock_threshold']);
        });

        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            Schema::table('products', function (Blueprint $table): void {
                $table->fullText(['name', 'code', 'barcode']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
