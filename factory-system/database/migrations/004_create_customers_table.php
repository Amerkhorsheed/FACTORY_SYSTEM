<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create customers table with credit balance fields.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('code', 30)->unique();
            $table->string('name', 150);
            $table->string('business_name', 200)->nullable();
            $table->string('phone', 30)->unique();
            $table->string('phone_alt', 30)->nullable();
            $table->string('email', 150)->nullable();
            $table->text('address');
            $table->string('city', 100)->nullable();
            $table->string('region', 100)->nullable();
            $table->enum('category', ['A', 'B', 'C'])->default('B');
            $table->unsignedBigInteger('credit_limit')->default(0);
            $table->unsignedBigInteger('outstanding_balance')->default(0);
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('portal_access')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['category', 'region', 'is_active']);
            $table->index('outstanding_balance');
        });

        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            Schema::table('customers', function (Blueprint $table): void {
                $table->fullText(['name', 'business_name', 'phone']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
