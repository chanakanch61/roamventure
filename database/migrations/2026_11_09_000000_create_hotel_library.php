<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Hotels kept for reuse across programs: a name and its photos. A program copies the
     * hotel into its day when picked, so editing the library never changes a program already entered.
     */
    public function up(): void
    {
        Schema::create('hotel_library', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 200)->unique();
            $table->mediumText('images_json');
            $table->dateTime('created_at');
            $table->dateTime('updated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hotel_library');
    }
};
