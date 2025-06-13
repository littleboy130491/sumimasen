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
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->json('title');
            $table->json('slug');
            $table->json('content')->nullable();
            $table->json('section')->nullable();
            $table->json('excerpt')->nullable();
            $table->json('custom_fields')->nullable();
            $table->string('featured_image', 255)->nullable();
            $table->string('template', 255)->nullable();
            $table->integer('menu_order')->default(0);
            $table->bigInteger('parent_id')->nullable()->index()->unsigned();
            $table->enum('status', ['draft', 'published', 'scheduled'])->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->foreignId('author_id')->constrained('users', 'id');
            $table->foreign('parent_id')->references('id')->on('pages');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
