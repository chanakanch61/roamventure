<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Saved line items for invoices of other charges (visa fee, visa service, travel insurance ...).
     * Picking one fills the description, unit, default price and the disbursement flag; staff can still
     * choose "other" and type a line that is not saved here. Invoices copy the values, so editing or
     * deleting a template never changes an invoice already made.
     */
    public function up(): void
    {
        Schema::create('invoice_item_templates', function (Blueprint $table) {
            $table->increments('id');
            $table->string('description', 300);
            $table->string('unit', 30)->default('');
            $table->decimal('price', 12, 2)->default(0);
            $table->boolean('disbursement')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->dateTime('updated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_item_templates');
    }
};
