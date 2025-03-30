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
    Schema::create('feedback', function (Blueprint $table) {
      $table->id();
      $table->foreignId('user_id')->constrained()->onDelete('cascade');
      $table->string('type'); // suggestion, complaint, praise, question, other
      $table->text('content');
      $table->string('status')->default('pending'); // pending, in_review, resolved, closed
      $table->text('response')->nullable();
      $table->foreignId('responded_by')->nullable()->constrained('users');
      $table->timestamp('responded_at')->nullable();
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('feedback');
  }
};
