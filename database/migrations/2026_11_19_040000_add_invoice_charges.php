<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Invoices for costs outside the tour price (visa fees, extra nights, sundries).
     * kind = installment: a share of a quotation, as before.
     * kind = charges: its own line items; may point at a quotation or a booking, or at nothing.
     * Items marked as a disbursement (เงินทดรองจ่าย, e.g. the embassy's visa fee) carry no VAT and no
     * withholding; their sum is kept in exempt_amount, so total = subtotal + vat_amount + exempt_amount.
     */
    public function up(): void
    {
        Schema::table('group_invoices', function (Blueprint $table) {
            $table->string('kind', 12)->default('installment')->after('number')->index();
            $table->unsignedInteger('quotation_id')->nullable()->change();
            $table->unsignedInteger('booking_id')->nullable()->after('request_id')->index();
            $table->string('booking_reference', 40)->default('')->after('quote_total');
            $table->mediumText('items')->nullable()->after('wht_rate');
            $table->decimal('exempt_amount', 12, 2)->default(0)->after('vat_amount');
        });
    }

    public function down(): void
    {
        Schema::table('group_invoices', function (Blueprint $table) {
            $table->dropIndex(['kind']);
            $table->dropIndex(['booking_id']);
            $table->dropColumn(['kind', 'booking_id', 'booking_reference', 'items', 'exempt_amount']);
        });
    }
};
