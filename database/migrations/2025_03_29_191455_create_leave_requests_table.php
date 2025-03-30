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
    Schema::create('leave_requests', function (Blueprint $table) {
      $table->id();
      $table->foreignId('user_id')->constrained()->onDelete('cascade');
      $table->date('start_date');
      $table->date('end_date');
      $table->string('leave_type'); // annual, sick, personal, unpaid
      $table->integer('duration_days');
      $table->text('reason');
      $table->string('status')->default('pending'); // pending, approved, rejected, cancelled
      $table->foreignId('approved_by')->nullable()->constrained('users');
      $table->text('rejection_reason')->nullable();
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('leave_requests');
  }
};
