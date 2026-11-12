<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Overall hotel star level of a program (1–5), shown in the private price table.
     * Existing programs start at 3 stars; NULL means the editor chose "not specified".
     */
    public function up(): void
    {
        Schema::table('tours', function (Blueprint $table) {
            $table->unsignedTinyInteger('hotel_stars')->nullable()->default(3)->after('hotel_details');
        });
    }

    public function down(): void
    {
        Schema::table('tours', function (Blueprint $table) {
            $table->dropColumn('hotel_stars');
        });
    }
};
