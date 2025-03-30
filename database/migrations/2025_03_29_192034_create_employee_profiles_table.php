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
    Schema::create('employee_profiles', function (Blueprint $table) {
      $table->id();
      $table->foreignId('user_id')->constrained()->onDelete('cascade');
      $table->foreignId('department_id')->constrained();
      $table->string('position');
      $table->date('hire_date');
      $table->decimal('base_salary', 10, 2);
      $table->decimal('hourly_rate', 8, 2)->nullable();
      $table->string('status')->default('active'); // active, on_leave, suspended, terminated
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('employee_profiles');
  }
};
