<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Splits what used to live in one terms field, so the detail page can show price
     * coverage, payment, cancellation and travel advice as separate sections. Existing
     * programs stay NULL and keep showing their terms text until an editor fills these in.
     */
    public function up(): void
    {
        Schema::table('tours', function (Blueprint $table) {
            $table->text('inclusions')->nullable()->after('photographer_details');
            $table->text('payment_terms')->nullable()->after('terms');
            $table->text('cancellation_terms')->nullable()->after('payment_terms');
            $table->text('know_before_go')->nullable()->after('cancellation_terms');
        });
    }

    public function down(): void
    {
        Schema::table('tours', function (Blueprint $table) {
            $table->dropColumn(['inclusions', 'payment_terms', 'cancellation_terms', 'know_before_go']);
        });
    }
};
