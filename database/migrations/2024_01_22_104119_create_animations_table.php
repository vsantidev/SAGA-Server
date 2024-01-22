<?php

use App\Models\Evenement;
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
        Schema::create('animations', function (Blueprint $table) {
            $table->id();
            $table->string('title',255);
            $table->longText('content');
            $table->string('picture');
            $table->string('animateur');
            $table->int('fight');
            $table->int('reflection');
            $table->int('roleplay');
            $table->string('type_animation');
            $table->foreignIdFor(Evenement::class)->constrained;
            // $table->foreignIdFor()
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('animations');
    }
};
