<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A departure may sell below its regular price. price_per_person (and each private rate) keeps the
     * regular price; public pages and new bookings use it less discount_percent until discount_until
     * (inclusive, null = no end). See App\Support\DepartureDiscount.
     */
    public function up(): void
    {
        Schema::table('departures', function (Blueprint $table) {
            $table->unsignedTinyInteger('discount_percent')->default(0)->after('price_per_person');
            $table->date('discount_until')->nullable()->after('discount_percent');
        });
    }

    public function down(): void
    {
        Schema::table('departures', function (Blueprint $table) {
            $table->dropColumn(['discount_percent', 'discount_until']);
        });
    }
};
