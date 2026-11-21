<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Категорії: «Кухня», «Одяг» → підкатегорії «Худі», «Світшоти» (чипи в каталозі)
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('image')->nullable();
            $table->text('description')->nullable();
            $table->string('seo_title')->nullable();
            $table->string('seo_description', 500)->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('short_description', 500)->nullable();
            $table->longText('description')->nullable();
            $table->json('specs')->nullable();            // характеристики: [{label, value}]
            $table->decimal('price', 10, 2);              // базова ціна, ₴
            $table->decimal('old_price', 10, 2)->nullable(); // закреслена ціна для «−20%»
            $table->string('badge')->nullable();          // new | hit
            $table->boolean('is_apparel')->default(false); // одяг: колір, розмір, таблиця розмірів
            $table->string('fit_hint')->nullable();       // «Сідає розмір у розмір»
            $table->json('size_chart')->nullable();
            $table->decimal('rating', 2, 1)->nullable();  // кеш середньої оцінки
            $table->unsignedInteger('reviews_count')->default(0);
            $table->string('seo_title')->nullable();
            $table->string('seo_description', 500)->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'category_id']);
        });

        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('alt')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        // Варіант = те, що реально лежить на складі: колір × розмір з залишком.
        // У товарів без варіантів буде один варіант без кольору й розміру.
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('sku')->unique();
            $table->string('color')->nullable();
            $table->string('color_hex', 7)->nullable();
            $table->string('size')->nullable();
            $table->decimal('price', 10, 2)->nullable(); // якщо ціна варіанта відрізняється
            $table->unsignedInteger('stock')->default(0);
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['product_id', 'color', 'size']);
        });

        // Підбірки: «на блекаут», «подарунок до 500 ₴» — лендинги для реклами й SEO
        Schema::create('collections', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('image')->nullable();
            $table->text('description')->nullable();
            $table->string('seo_title')->nullable();
            $table->string('seo_description', 500)->nullable();
            $table->boolean('show_on_home')->default(false);
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('collection_product', function (Blueprint $table) {
            $table->foreignId('collection_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sort')->default(0);
            $table->primary(['collection_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collection_product');
        Schema::dropIfExists('collections');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('product_images');
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
    }
};
