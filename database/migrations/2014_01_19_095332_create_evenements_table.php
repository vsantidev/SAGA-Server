<?php

use App\Models\Site;
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
        Schema::create('evenements', function (Blueprint $table) {
            $table->id();
            $table->string('logo')->nullable();
            $table->boolean('actif');
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->mediumText('content')->nullable();
            $table->datetime('date_opening');
            $table->datetime('date_ending');
            $table->string('flag')->nullable();
            $table->string('display')->nullable();
            $table->string('attachment')->nullable();
            $table->longText('others')->nullable();
            $table->mediumText('announcement')->nullable();
            $table->boolean('hide_announcement')->nullable();
            $table->boolean('hide_animation')->nullable();
            $table->string('url_event')->nullable();
            $table->string('url_inscritpion')->nullable();
            $table->foreignIdFor(Site::class)->constrained;
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conventions');
    }
};
