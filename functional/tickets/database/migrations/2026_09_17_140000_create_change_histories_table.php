<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('change_histories', function (Blueprint $table) {
            $table->id();
            $table->morphs('historizable');
            $table->string('attribute');
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->foreignId('author_id')->nullable()->constrained('users');
            $table->string('author_kind');
            $table->timestamp('created_at')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('change_histories');
    }
};
