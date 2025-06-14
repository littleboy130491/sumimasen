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
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->text('content');
            $table->text('name');
            $table->text('email');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->bigInteger('commentable_id')->index()->unsigned();
            $table->string('commentable_type', 255)->index();
            $table->bigInteger('parent_id')->nullable()->index()->unsigned();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
