<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Hotels a day may use, in priority order: the first is the main choice and each later
     * one is the fallback when the one before it is unavailable. Medium text because the
     * number of photos per hotel is not capped. The free-text hotel column stays as is.
     */
    public function up(): void
    {
        Schema::table('tour_days', function (Blueprint $table) {
            $table->mediumText('hotels_json')->nullable()->after('images_json');
        });
    }

    public function down(): void
    {
        Schema::table('tour_days', function (Blueprint $table) {
            $table->dropColumn('hotels_json');
        });
    }
};
