<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Charter group tours (/grouptour): requests customers send through the public form, and the past
     * groups shown on the page as references. A past group may point at a Gallery album for its photos.
     */
    public function up(): void
    {
        Schema::create('group_requests', function (Blueprint $table) {
            $table->increments('id');
            $table->string('reference', 40)->unique();
            $table->string('status', 20)->default('new')->index();
            $table->string('contact_name', 120);
            $table->string('organization', 180)->default('');
            $table->string('phone', 25);
            $table->string('email', 190)->default('');
            $table->string('line_id', 100)->default('');
            $table->string('group_type', 40);
            $table->string('destination', 200);
            $table->date('date_from')->nullable();
            $table->date('date_to')->nullable();
            $table->string('date_note', 120)->default('');
            $table->unsignedTinyInteger('days')->nullable();
            $table->unsignedSmallInteger('adults');
            $table->unsignedSmallInteger('children')->default(0);
            $table->string('budget', 30)->default('');
            $table->string('hotel_level', 30)->default('');
            $table->string('services', 500)->default('');
            $table->text('message')->nullable();
            $table->string('source', 200)->default('');
            $table->text('admin_note')->nullable();
            $table->dateTime('created_at');
            $table->dateTime('updated_at');
        });

        Schema::create('group_trips', function (Blueprint $table) {
            $table->increments('id');
            $table->string('slug', 180)->unique();
            $table->string('title', 200);
            $table->string('group_type', 40);
            $table->string('organization', 180)->default('');
            $table->string('destination', 200);
            $table->date('travel_date')->nullable();
            $table->unsignedTinyInteger('days')->nullable();
            $table->unsignedSmallInteger('travelers')->nullable();
            $table->string('summary', 400)->default('');
            $table->text('story')->nullable();
            $table->text('highlights')->nullable();
            $table->text('testimonial')->nullable();
            $table->string('testimonial_by', 180)->default('');
            $table->string('cover', 255)->default('');
            $table->unsignedInteger('album_id')->nullable();
            $table->string('seo_title', 200)->default('');
            $table->string('seo_description', 300)->default('');
            $table->string('status', 20)->default('draft')->index();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->unsignedInteger('request_id')->nullable();
            $table->dateTime('created_at');
            $table->dateTime('updated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_trips');
        Schema::dropIfExists('group_requests');
    }
};
