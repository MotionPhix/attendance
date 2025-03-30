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
    Schema::create('salary_records', function (Blueprint $table) {
      $table->id();
      $table->foreignId('user_id')->constrained()->onDelete('cascade');
      $table->integer('month');
      $table->integer('year');
      $table->decimal('base_amount', 10, 2);
      $table->decimal('deductions', 10, 2)->default(0);
      $table->decimal('bonuses', 10, 2)->default(0);
      $table->decimal('overtime_pay', 10, 2)->default(0);
      $table->decimal('net_amount', 10, 2);
      $table->string('status')->default('pending'); // pending, processed, paid
      $table->timestamp('processed_at')->nullable();
      $table->timestamp('paid_at')->nullable();
      $table->timestamps();

      // Ensure only one salary record per user per month/year
      $table->unique(['user_id', 'month', 'year']);
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('salary_records');
  }
};
