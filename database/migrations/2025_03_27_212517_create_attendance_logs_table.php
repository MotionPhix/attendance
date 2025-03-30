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
    Schema::create('attendance_logs', function (Blueprint $table) {
      $table->id();
      $table->foreignId('user_id')->constrained()->onDelete('cascade');
      $table->dateTime('check_in_time');
      $table->dateTime('check_out_time')->nullable();
      $table->integer('late_minutes')->default(0);
      $table->integer('early_departure_minutes')->default(0);
      $table->string('status')->default('on_time'); // on_time, late, early_departure, late_and_early_departure
      $table->text('notes')->nullable();
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('attendance_logs');
  }
};
