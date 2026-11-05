<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Reusable vehicle charts, typically one per land operator. A program copies a template's
     * rows when it is applied, so editing a template never changes a program already entered.
     */
    public function up(): void
    {
        Schema::create('vehicle_templates', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 120)->unique();
            $table->text('vehicle_rows');
            $table->dateTime('created_at');
            $table->dateTime('updated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_templates');
    }
};
