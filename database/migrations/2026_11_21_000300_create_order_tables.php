<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('number')->nullable()->unique();      // номер для покупця: B-100001 (ставимо після insert)
            $table->uuid('public_token')->unique();              // посилання «Де моє замовлення» без входу
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete(); // null — гість
            $table->string('status')->default('new');            // OrderStatus
            $table->boolean('is_quick_buy')->default(false);     // «Купити в 1 клік»

            // Контакти отримувача (знімок на момент замовлення)
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('phone', 20);
            $table->string('email')->nullable();

            // Доставка
            $table->string('delivery_method')->nullable();       // DeliveryMethod
            $table->string('np_city_ref')->nullable();
            $table->string('city')->nullable();
            $table->string('np_warehouse_ref')->nullable();
            $table->string('warehouse')->nullable();
            $table->string('street')->nullable();
            $table->string('building')->nullable();
            $table->string('apartment')->nullable();
            $table->string('ttn')->nullable()->index();          // номер накладної НП
            $table->string('np_status')->nullable();             // останній статус від НП

            // Оплата
            $table->string('payment_method')->nullable();        // PaymentMethod
            $table->string('payment_status')->default('pending'); // PaymentStatus
            $table->string('payment_provider_id')->nullable();
            $table->timestamp('paid_at')->nullable();

            // Суми, ₴
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('delivery_cost', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);

            $table->text('comment')->nullable();                 // коментар покупця
            $table->text('manager_note')->nullable();            // внутрішня нотатка
            $table->boolean('dont_call')->default(false);        // «Не дзвонити для підтвердження»
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();
            // Знімок товару: якщо товар змінять чи видалять, замовлення лишиться правильним
            $table->string('name');
            $table->string('sku')->nullable();
            $table->string('variant_label')->nullable();         // «Оливковий, M»
            $table->decimal('price', 10, 2);
            $table->unsignedInteger('quantity');
            $table->decimal('total', 10, 2);
            $table->timestamps();
        });

        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete(); // «Перевірена покупка»
            $table->string('author_name');
            $table->unsignedTinyInteger('rating');
            $table->text('text')->nullable();
            $table->string('variant_label')->nullable();         // «Купив розмір M»
            $table->boolean('is_published')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
