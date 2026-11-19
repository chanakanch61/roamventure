<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Extra costs added to a booking's own bill (visa fee, extra night ...), when the customer wants to pay
     * everything together. The booking invoice's total_amount = bookings.total_price + the sum of these lines,
     * so the existing payment flow (remaining balance, slips, receipts) covers them without a separate invoice.
     */
    public function up(): void
    {
        Schema::create('booking_charges', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('booking_id')->index();
            $table->string('description', 300);
            $table->decimal('qty', 10, 2);
            $table->string('unit', 30)->default('');
            $table->decimal('price', 12, 2);
            $table->decimal('amount', 14, 2);
            $table->unsignedInteger('created_by')->nullable();
            $table->dateTime('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_charges');
    }
};
