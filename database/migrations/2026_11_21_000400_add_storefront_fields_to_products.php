<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Поля, яких вимагають екрани товару з bazarik-handoff (MobileProduct, MobileApparel, DeskProduct). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // «Простими словами»: [{value: "~4 зарядки", text: "смартфона на одному заряді"}]
            $table->json('highlights')->nullable()->after('specs');
            // «Склад і догляд»
            $table->text('care')->nullable()->after('highlights');
            // «Модель на фото: зріст 172 см, розмір M»
            $table->string('model_note')->nullable()->after('fit_hint');
            // «Разом дешевше»: товар у наборі й знижка на набір, %
            $table->foreignId('bundle_product_id')->nullable()->after('model_note')->constrained('products')->nullOnDelete();
            $table->unsignedTinyInteger('bundle_discount')->nullable()->after('bundle_product_id');
            // Відео з Reels/TikTok на сторінці товару
            $table->string('video_url')->nullable()->after('bundle_discount');
            $table->unsignedSmallInteger('video_seconds')->nullable()->after('video_url');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('bundle_product_id');
            $table->dropColumn(['highlights', 'care', 'model_note', 'bundle_discount', 'video_url', 'video_seconds']);
        });
    }
};
