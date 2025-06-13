<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('seo_suite', function (Blueprint $table) {
            $table->id();

            $table->morphs('model');
            $table->string('title')->nullable();
            $table->string('description')->nullable();
            $table->string('canonical_url')->nullable();
            $table->json('metas')->nullable();

            // Open Graph
            $table->string('og_title')->nullable();
            $table->string('og_description')->nullable();
            $table->string('og_type')->nullable();
            $table->json('og_type_details')->nullable();
            $table->json('og_properties')->nullable();

            // X (Formerly Twitter)
            $table->string('x_card_type')->nullable();
            $table->string('x_title')->nullable();
            $table->string('x_site')->nullable();


            $table->boolean('noindex')->default(false);
            $table->boolean('nofollow')->default(false);

            $table->timestamps();
        });
    }
};
