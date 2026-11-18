<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Orders from every sales channel (site, Mercado Livre, Shopee, TikTok) share these tables.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('channel')->index();
            $table->string('external_id')->nullable();
            $table->string('status')->index();
            $table->string('customer_name');
            $table->string('customer_email')->nullable();
            $table->string('customer_phone')->nullable();
            $table->string('customer_document', 18)->nullable();
            $table->string('shipping_zip', 9)->nullable();
            $table->string('shipping_street')->nullable();
            $table->string('shipping_number', 20)->nullable();
            $table->string('shipping_complement')->nullable();
            $table->string('shipping_district')->nullable();
            $table->string('shipping_city')->nullable();
            $table->string('shipping_state', 2)->nullable();
            $table->unsignedInteger('subtotal');
            $table->unsignedInteger('shipping_cost')->default(0);
            $table->unsignedInteger('discount')->default(0);
            $table->unsignedInteger('total');
            $table->text('notes')->nullable();
            $table->timestamp('placed_at');
            $table->timestamps();

            $table->unique(['channel', 'external_id']);
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name');
            $table->string('variant_name')->nullable();
            $table->string('sku')->nullable();
            $table->unsignedInteger('unit_price');
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('total');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
