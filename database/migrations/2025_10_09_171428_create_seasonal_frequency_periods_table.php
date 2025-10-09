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
        Schema::create('seasonal_frequency_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_schedule_id')->constrained()->cascadeOnDelete();
            $table->integer('start_month')->comment('1-12');
            $table->integer('start_day')->comment('1-31');
            $table->integer('end_month')->comment('1-12');
            $table->integer('end_day')->comment('1-31');
            $table->enum('frequency', [
                'daily',
                'every_5_days',
                'every_7_days',
                'weekly',
                'biweekly',
                'every_3_weeks',
                'monthly',
                'quarterly',
            ]);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['service_schedule_id', 'start_month', 'start_day']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seasonal_frequency_periods');
    }
};
