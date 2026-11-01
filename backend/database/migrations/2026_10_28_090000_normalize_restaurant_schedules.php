<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Make the schedule the only source of truth.
     *
     * The raw natural-language string is no longer stored: parsed shifts in
     * restaurant_schedules are the canonical representation. Database-level
     * constraints now enforce the invariants the application relies on:
     *
     * - one window per restaurant/day may only exist once,
     * - day_of_week is a real ISO-8601 weekday,
     * - a window has a real duration,
     * - spans_next_day always agrees with the clock values.
     */
    public function up(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropColumn('raw_schedule');
        });

        Schema::table('restaurant_schedules', function (Blueprint $table) {
            $table->unique(
                ['restaurant_id', 'day_of_week', 'open_time', 'close_time'],
                'restaurant_schedules_window_unique'
            );
        });

        DB::statement(
            'ALTER TABLE restaurant_schedules
             ADD CONSTRAINT restaurant_schedules_day_of_week_check
             CHECK (day_of_week BETWEEN 1 AND 7)'
        );

        DB::statement(
            'ALTER TABLE restaurant_schedules
             ADD CONSTRAINT restaurant_schedules_window_duration_check
             CHECK (open_time <> close_time)'
        );

        DB::statement(
            'ALTER TABLE restaurant_schedules
             ADD CONSTRAINT restaurant_schedules_spans_next_day_check
             CHECK (spans_next_day = (close_time < open_time))'
        );
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE restaurant_schedules DROP CONSTRAINT IF EXISTS restaurant_schedules_spans_next_day_check');
        DB::statement('ALTER TABLE restaurant_schedules DROP CONSTRAINT IF EXISTS restaurant_schedules_window_duration_check');
        DB::statement('ALTER TABLE restaurant_schedules DROP CONSTRAINT IF EXISTS restaurant_schedules_day_of_week_check');

        Schema::table('restaurant_schedules', function (Blueprint $table) {
            $table->dropUnique('restaurant_schedules_window_unique');
        });

        Schema::table('restaurants', function (Blueprint $table) {
            $table->text('raw_schedule')->nullable()->after('name');
        });
    }
};
