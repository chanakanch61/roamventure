<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Company practice: when the customer withholds tax, the withholding rate is added onto each unit price
     * first (price × (1 + rate)), then multiplied by quantity. Items keep the base price staff typed;
     * the price printed is worked out from it, so changing the rate never leaves stale figures.
     */
    public function up(): void
    {
        Schema::table('group_quotations', function (Blueprint $table) {
            $table->boolean('wht_gross_up')->default(false)->after('wht_rate');
        });
    }

    public function down(): void
    {
        Schema::table('group_quotations', function (Blueprint $table) {
            $table->dropColumn('wht_gross_up');
        });
    }
};
