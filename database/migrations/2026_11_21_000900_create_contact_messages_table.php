<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Повідомлення з форми «Напишіть нам» на сторінці «Контакти» */
    public function up(): void
    {
        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete(); // якщо писав покупець з кабінетом
            $table->string('name', 100);
            $table->string('phone', 20);                 // +380XXXXXXXXX
            $table->text('message');
            $table->string('ip', 45)->nullable();
            $table->timestamp('handled_at')->nullable(); // менеджер відповів
            $table->timestamps();

            $table->index(['handled_at', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_messages');
    }
};
