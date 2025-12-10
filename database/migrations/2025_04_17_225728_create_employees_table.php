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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('dni')->unique();
            $table->string('paternal_surname');
            $table->string('maternal_surname');
            $table->string('names');
            $table->foreignId('position_id')
                ->constrained()
                ->onUpdate('cascade')
                ->onDelete('no action');
            $table->string('email')->unique() ->nullable();
            $table->date('date_of_birth');
            $table->bigInteger('phone') ->unique();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
