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
        Schema::table('attendance_marks', function (Blueprint $table) {
            // Drop existing foreign keys
            $table->dropForeign(['attendance_type_id']);
            $table->dropForeign(['shift_id']);

            // Recreate foreign keys with cascade delete
            $table->foreign('attendance_type_id')
                ->references('id')
                ->on('attendance_types')
                ->onDelete('cascade');
            
            $table->foreign('shift_id')
                ->references('id')
                ->on('shifts')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendance_marks', function (Blueprint $table) {
            // Drop cascade foreign keys
            $table->dropForeign(['attendance_type_id']);
            $table->dropForeign(['shift_id']);

            // Recreate with no action
            $table->foreign('attendance_type_id')
                ->references('id')
                ->on('attendance_types')
                ->onDelete('no action');
            
            $table->foreign('shift_id')
                ->references('id')
                ->on('shifts')
                ->onDelete('no action');
        });
    }
};
