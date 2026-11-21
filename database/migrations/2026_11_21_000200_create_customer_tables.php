<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Покупці: вхід за телефоном без пароля, ім'я питаємо лише в нових
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 20)->unique(); // E.164: +380XXXXXXXXX
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->timestamp('phone_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        // Одноразові коди з SMS
        Schema::create('phone_otps', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 20)->index();
            $table->string('code_hash');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamps();
        });

        // Адреси доставки (відділення / поштомат / кур'єр Нової Пошти)
        Schema::create('customer_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('recipient_name');
            $table->string('recipient_phone', 20);
            $table->string('delivery_method');           // DeliveryMethod enum
            $table->string('np_city_ref')->nullable();
            $table->string('city');
            $table->string('np_warehouse_ref')->nullable();
            $table->string('warehouse')->nullable();     // «Відділення №12, вул. ...»
            $table->string('street')->nullable();        // для кур'єра
            $table->string('building')->nullable();
            $table->string('apartment')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('favorites', function (Blueprint $table) {
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->timestamp('created_at')->nullable();
            $table->primary(['customer_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favorites');
        Schema::dropIfExists('customer_addresses');
        Schema::dropIfExists('phone_otps');
        Schema::dropIfExists('customers');
    }
};
