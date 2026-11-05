<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Separates ticket-included tours from land-only tours. Bookings keep their own copy
     * so later edits to a program do not change what an earlier request was sold; requests
     * made before this column existed stay NULL because the answer is not known.
     */
    public function up(): void
    {
        Schema::table('tours', function (Blueprint $table) {
            $table->boolean('flight_included')->default(false)->after('airline');
        });
        Schema::table('bookings', function (Blueprint $table) {
            $table->boolean('flight_included')->nullable()->after('tour_title');
        });
    }

    public function down(): void
    {
        Schema::table('tours', function (Blueprint $table) {
            $table->dropColumn('flight_included');
        });
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('flight_included');
        });
    }
};
