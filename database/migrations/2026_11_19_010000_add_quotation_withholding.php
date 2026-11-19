<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Withholding tax on group quotations: a juristic-person customer deducts it when paying
     * (commonly 3% of the amount before VAT) and sends a 50 Tawi certificate. Optional per quotation.
     */
    public function up(): void
    {
        Schema::table('group_quotations', function (Blueprint $table) {
            $table->boolean('wht_enabled')->default(false)->after('total');
            $table->decimal('wht_rate', 5, 2)->default(3)->after('wht_enabled');
            $table->decimal('wht_amount', 12, 2)->default(0)->after('wht_rate');
            $table->decimal('net_payable', 12, 2)->default(0)->after('wht_amount');
        });
    }

    public function down(): void
    {
        Schema::table('group_quotations', function (Blueprint $table) {
            $table->dropColumn(['wht_enabled', 'wht_rate', 'wht_amount', 'net_payable']);
        });
    }
};
