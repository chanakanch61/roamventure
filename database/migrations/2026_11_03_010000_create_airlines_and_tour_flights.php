<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Flights live in named sets on a program, and each departure picks a set, so rounds that
     * fly the same schedule share one record. Bookings keep a copy of the flights they were
     * sold with, because editing a program must not change what an earlier request shows.
     */
    public function up(): void
    {
        Schema::create('airlines', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 120)->unique();
            $table->string('code', 3)->default('');
            $table->string('logo_path')->default('');
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->dateTime('created_at');
            $table->dateTime('updated_at');
        });
        Schema::create('tour_flight_sets', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('tour_id');
            $table->string('name', 120);
            $table->integer('sort_order')->default(0);
            $table->foreign('tour_id')->references('id')->on('tours')->cascadeOnDelete();
        });
        Schema::create('tour_flights', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('flight_set_id');
            $table->unsignedInteger('airline_id');
            $table->string('leg_type', 10);
            $table->unsignedSmallInteger('day_number')->nullable();
            $table->string('flight_number', 12);
            $table->string('from_airport', 40);
            $table->string('to_airport', 40);
            $table->char('depart_time', 5);
            $table->char('arrive_time', 5);
            $table->boolean('arrive_next_day')->default(false);
            // Entered by hand: local departure and arrival times cannot give it across time zones.
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->boolean('included')->default(false);
            $table->integer('sort_order')->default(0);
            $table->foreign('flight_set_id')->references('id')->on('tour_flight_sets')->cascadeOnDelete();
            $table->foreign('airline_id')->references('id')->on('airlines');
        });
        Schema::table('departures', function (Blueprint $table) {
            $table->unsignedInteger('flight_set_id')->nullable();
            $table->foreign('flight_set_id')->references('id')->on('tour_flight_sets')->nullOnDelete();
        });
        Schema::table('bookings', function (Blueprint $table) {
            $table->mediumText('flight_snapshot')->nullable()->after('flight_included');
            $table->boolean('ticket_help')->default(false)->after('flight_snapshot');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['flight_snapshot', 'ticket_help']);
        });
        Schema::table('departures', function (Blueprint $table) {
            $table->dropForeign(['flight_set_id']);
            $table->dropColumn('flight_set_id');
        });
        Schema::dropIfExists('tour_flights');
        Schema::dropIfExists('tour_flight_sets');
        Schema::dropIfExists('airlines');
    }
};
