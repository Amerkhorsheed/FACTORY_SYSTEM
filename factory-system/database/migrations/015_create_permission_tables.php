<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create Spatie permission tables in strict project order.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');
        $modelKey = $columnNames['model_morph_key'] ?? 'model_id';
        $rolePivot = $columnNames['role_pivot_key'] ?? 'role_id';
        $permissionPivot = $columnNames['permission_pivot_key'] ?? 'permission_id';

        Schema::create($tableNames['permissions'], function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();

            $table->unique(['name', 'guard_name']);
        });

        Schema::create($tableNames['roles'], function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();

            $table->unique(['name', 'guard_name']);
        });

        Schema::create($tableNames['model_has_permissions'], function (Blueprint $table) use ($tableNames, $modelKey, $permissionPivot): void {
            $table->unsignedBigInteger($permissionPivot);
            $table->string('model_type');
            $table->unsignedBigInteger($modelKey);

            $table->foreign($permissionPivot)->references('id')->on($tableNames['permissions'])->cascadeOnDelete();
            $table->index([$modelKey, 'model_type'], 'model_has_permissions_model_id_model_type_index');
            $table->primary([$permissionPivot, $modelKey, 'model_type'], 'model_has_permissions_permission_model_type_primary');
        });

        Schema::create($tableNames['model_has_roles'], function (Blueprint $table) use ($tableNames, $modelKey, $rolePivot): void {
            $table->unsignedBigInteger($rolePivot);
            $table->string('model_type');
            $table->unsignedBigInteger($modelKey);

            $table->foreign($rolePivot)->references('id')->on($tableNames['roles'])->cascadeOnDelete();
            $table->index([$modelKey, 'model_type'], 'model_has_roles_model_id_model_type_index');
            $table->primary([$rolePivot, $modelKey, 'model_type'], 'model_has_roles_role_model_type_primary');
        });

        Schema::create($tableNames['role_has_permissions'], function (Blueprint $table) use ($tableNames, $rolePivot, $permissionPivot): void {
            $table->unsignedBigInteger($permissionPivot);
            $table->unsignedBigInteger($rolePivot);

            $table->foreign($permissionPivot)->references('id')->on($tableNames['permissions'])->cascadeOnDelete();
            $table->foreign($rolePivot)->references('id')->on($tableNames['roles'])->cascadeOnDelete();
            $table->primary([$permissionPivot, $rolePivot], 'role_has_permissions_permission_id_role_id_primary');
        });

        app('cache')->store(config('permission.cache.store') !== 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableNames = config('permission.table_names');

        Schema::dropIfExists($tableNames['role_has_permissions']);
        Schema::dropIfExists($tableNames['model_has_roles']);
        Schema::dropIfExists($tableNames['model_has_permissions']);
        Schema::dropIfExists($tableNames['roles']);
        Schema::dropIfExists($tableNames['permissions']);
    }
};
