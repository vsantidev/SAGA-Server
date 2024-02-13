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
            $table->string('subtitle');
            $table->mediumText('content');
            $table->datetime('date_opening');
            $table->datetime('date_ending');
            $table->string('flag')->nullable();
            $table->string('display')->nullable();
            $table->string('attachment');
            $table->longText('others');
            $table->mediumText('announcement');
            $table->boolean('hide_announcement');
            $table->boolean('hide_animation');
            $table->string('url_event');
            $table->string('url_inscritpion');
            $table->foreignIdFor(Site::class)->constrained;
            $table->timestamps();
        });

        Schema::create('evenement_user', function(Blueprint $table) {
            $table->foreignIdFor(\App\Models\Evenement::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(\App\Models\User::class)->constrained()->cascadeOnDelete();
            $table->boolean('masters')->default(false);
            $table->boolean('rewards')->default(false);
            $table->primary(['evenement_id', 'user_id']);
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
