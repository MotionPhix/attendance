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
      $table->string('leave_type_id'); // annual, sick, personal, unpaid
      $table->integer('total_days');
      $table->text('review_notes')->nullable();
      $table->string('status')->default('pending'); // pending, approved, rejected, cancelled
      $table->foreignId('reviewed_by')->nullable()->constrained('users');
      $table->timestamp('submitted_at')->nullable();
      $table->timestamp('reviewed_at')->nullable();
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
