<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Quotations for charter groups, made in the back office and printed / saved as PDF.
     * Line items are stored as JSON; totals are recalculated on the server at every save and stored,
     * so a printed quotation always shows the same figures as the list.
     */
    public function up(): void
    {
        Schema::create('group_quotations', function (Blueprint $table) {
            $table->increments('id');
            $table->string('number', 40)->unique();
            $table->unsignedInteger('request_id')->nullable()->index();
            $table->string('status', 20)->default('draft')->index();
            $table->date('issue_date');
            $table->date('valid_until')->nullable();
            $table->string('customer_name', 180);
            $table->string('customer_company', 200)->default('');
            $table->text('customer_address')->nullable();
            $table->string('customer_tax_id', 30)->default('');
            $table->string('customer_phone', 40)->default('');
            $table->string('customer_email', 190)->default('');
            $table->string('title', 250);
            $table->string('travel_dates', 150)->default('');
            $table->unsignedSmallInteger('travelers')->nullable();
            $table->mediumText('items');
            $table->decimal('discount', 12, 2)->default(0);
            $table->string('vat_mode', 10)->default('none');
            $table->decimal('vat_rate', 5, 2)->default(7);
            $table->decimal('items_total', 12, 2)->default(0);
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('vat_amount', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->text('includes')->nullable();
            $table->text('excludes')->nullable();
            $table->text('payment_terms')->nullable();
            $table->text('notes')->nullable();
            $table->string('prepared_by', 120)->default('');
            $table->dateTime('created_at');
            $table->dateTime('updated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_quotations');
    }
};
