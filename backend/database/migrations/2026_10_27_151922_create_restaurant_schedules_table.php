<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('restaurant_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week')->comment('1=Monday, 2=Tuesday, ..., 7=Sunday');
            $table->time('open_time');
            $table->time('close_time');
            $table->boolean('spans_next_day')->default(false)->comment('True if shift extends past midnight into next day');
            $table->timestamps();

            $table->index(['day_of_week', 'open_time', 'close_time']);
            $table->index(['restaurant_id', 'day_of_week']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('restaurant_schedules');
    }
};
