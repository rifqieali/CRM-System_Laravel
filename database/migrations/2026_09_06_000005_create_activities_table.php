<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->string('type', 50);
            $table->string('subject');
            $table->text('description')->nullable();
            $table->timestamp('due_at')->nullable()->index();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->morphs('activityable');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
