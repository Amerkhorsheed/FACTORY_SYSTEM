<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->dropIndex(['user_id']);
            $table->unique('user_id');
        });

        Schema::table('invoices', function (Blueprint $table): void {
            $table->unique('order_id');
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->foreignId('delivered_by')
                ->nullable()
                ->after('delivered_at')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('delivered_by');
        });

        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropUnique(['order_id']);
        });

        Schema::table('customers', function (Blueprint $table): void {
            $table->dropUnique(['user_id']);
            $table->index('user_id');
        });
    }
};
