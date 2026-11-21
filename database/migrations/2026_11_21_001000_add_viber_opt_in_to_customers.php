<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Згода покупця отримувати статуси замовлення й новини у Viber (крок «Ім'я» при вході) */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->boolean('viber_opt_in')->default(false)->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('viber_opt_in');
        });
    }
};
