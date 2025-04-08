<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  /**
   * Run the migrations.
   */
  public function up(): void
  {
    Schema::create('settings', function (Blueprint $table) {
      $table->id();
      $table->string('key')->unique();
      $table->text('value')->nullable();
      $table->string('group')->default('general');
      $table->string('type')->default('text'); // text, number, boolean, json, array
      $table->text('description')->nullable();
      $table->json('options')->nullable(); // For select/radio/checkbox options
      $table->boolean('is_public')->default(false); // Whether this setting is available to non-admin users
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('settings');
  }
};
