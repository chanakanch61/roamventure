<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Group size to vehicle chart, kept per program because suppliers differ.
     * Existing programs stay NULL and show no chart until an editor fills one in.
     */
    public function up(): void
    {
        Schema::table('tours', function (Blueprint $table) {
            $table->text('vehicle_options')->nullable()->after('transport_details');
        });
    }

    public function down(): void
    {
        Schema::table('tours', function (Blueprint $table) {
            $table->dropColumn('vehicle_options');
        });
    }
};
