<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Invoices billed from a group quotation, one per installment (deposit, balance, or the full amount),
     * and the receipt / tax invoice issued when staff record the payment.
     * Customer details and amounts are copied in when the invoice is made, so later edits to the
     * quotation never change a document already sent.
     */
    public function up(): void
    {
        Schema::create('group_invoices', function (Blueprint $table) {
            $table->increments('id');
            $table->string('number', 40)->unique();
            $table->unsignedInteger('quotation_id')->index();
            $table->unsignedInteger('request_id')->nullable()->index();
            $table->string('status', 20)->default('unpaid')->index();
            $table->string('label', 200);
            $table->string('share_type', 12);
            $table->decimal('share_value', 12, 2)->default(0);
            $table->date('issue_date');
            $table->date('due_date')->nullable();
            // Copied from the quotation.
            $table->string('quote_number', 40);
            $table->decimal('quote_total', 12, 2);
            $table->string('title', 250);
            $table->string('travel_dates', 150)->default('');
            $table->unsignedSmallInteger('travelers')->nullable();
            $table->string('customer_name', 180);
            $table->string('customer_company', 200)->default('');
            $table->text('customer_address')->nullable();
            $table->string('customer_tax_id', 30)->default('');
            $table->string('customer_branch', 60)->default('');
            $table->string('customer_phone', 40)->default('');
            $table->string('customer_email', 190)->default('');
            $table->string('seller_branch', 60)->default('สำนักงานใหญ่');
            $table->string('vat_mode', 10);
            $table->decimal('vat_rate', 5, 2);
            $table->boolean('wht_enabled')->default(false);
            $table->decimal('wht_rate', 5, 2)->default(0);
            // This installment.
            $table->decimal('subtotal', 12, 2);
            $table->decimal('vat_amount', 12, 2);
            $table->decimal('total', 12, 2);
            $table->decimal('wht_amount', 12, 2);
            $table->decimal('net_payable', 12, 2);
            $table->text('notes')->nullable();
            // Payment and receipt.
            $table->string('receipt_number', 40)->nullable()->unique();
            $table->date('paid_at')->nullable();
            $table->decimal('paid_amount', 12, 2)->nullable();
            $table->string('payment_method', 60)->default('');
            $table->string('payment_note', 255)->default('');
            $table->string('created_by', 120)->default('');
            $table->dateTime('created_at');
            $table->dateTime('updated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_invoices');
    }
};
