<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Database Products (catalog)
        Schema::create('database_products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type', 50)->default('mysql'); // mysql, mariadb, postgresql, mongodb, redis
            $table->text('description')->nullable();
            $table->string('host')->default('127.0.0.1');
            $table->unsignedInteger('port')->default(3306);
            $table->string('default_db_name')->nullable();
            $table->string('default_db_username')->nullable();
            $table->text('default_db_password')->nullable();
            $table->decimal('price', 15, 2)->default(0);
            $table->integer('stock')->default(100);
            $table->enum('status', ['available', 'sold_out', 'maintenance'])->default('available');
            $table->boolean('auto_provision')->default(true);
            $table->string('phpmyadmin_url')->nullable();
            $table->timestamps();
        });

        // 2. Orders
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 50)->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('database_product_id')->constrained('database_products')->cascadeOnDelete();
            $table->decimal('amount', 15, 2)->default(0);
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
            $table->enum('source', ['panel', 'paymenter'])->default('panel');
            $table->string('external_id')->nullable()->index(); // ID from Paymenter
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 3. User Databases (Actual deployed/assigned instances)
        Schema::create('user_databases', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('database_product_id')->constrained('database_products')->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('type', 50)->default('mysql');
            $table->string('host');
            $table->unsignedInteger('port')->default(3306);
            $table->string('database_name');
            $table->string('database_username');
            $table->text('database_password'); // encrypted
            $table->enum('status', ['active', 'suspended', 'terminated'])->default('active');
            $table->string('phpmyadmin_url')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        // 4. API Keys for External Integrations (Paymenter)
        Schema::create('api_keys', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('key_prefix', 16)->index();
            $table->string('key_hash', 128)->unique();
            $table->json('permissions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });

        // 5. Settings table
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key', 100)->primary();
            $table->longText('value')->nullable();
            $table->string('group', 50)->default('general');
            $table->timestamps();
        });

        // 6. Activity / Audit Logs
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 100);
            $table->text('description')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('api_keys');
        Schema::dropIfExists('user_databases');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('database_products');
    }
};
