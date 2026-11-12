<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A private package period can be open every day instead of between two dates.
     * Such a row stores placeholder dates (PrivatePackage::ANYTIME_START / ANYTIME_END);
     * its real window runs from today to advance_months ahead and is worked out on read.
     */
    public function up(): void
    {
        Schema::table('departures', function (Blueprint $table) {
            $table->boolean('anytime')->default(false)->after('end_date');
            $table->unsignedSmallInteger('advance_months')->nullable()->after('anytime');
        });
    }

    public function down(): void
    {
        Schema::table('departures', function (Blueprint $table) {
            $table->dropColumn(['anytime', 'advance_months']);
        });
    }
};
