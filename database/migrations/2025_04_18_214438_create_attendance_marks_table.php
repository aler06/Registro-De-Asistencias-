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
        Schema::create('attendance_marks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_type_id')
                ->constrained()
                ->onDelete('no action');
            $table->foreignId('shift_id')
                ->constrained()
                ->onDelete('no action');
            $table->foreignId('attendance_record_id')
                ->constrained()
                ->onDelete('cascade');
            $table->time('marked_time');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_marks');
    }
};
