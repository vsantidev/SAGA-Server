<?php

use App\Models\Evenement;
use App\Models\Room;
use App\Models\Type_animation;
use App\Models\User;
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
            $table->string('picture')->nullable();
            $table->string('animateur');
            $table->integer('fight');
            $table->integer('reflection');
            $table->integer('roleplay');
            $table->string('type_animation');
            $table->datetime('open_time');
            $table->datetime('closed_time');
            $table->integer('capacity');
            $table->foreignIdFor(User::class)->constrained;
            $table->foreignIdFor(Room::class)->constrained;
            $table->foreignIdFor(Evenement::class)->constrained;
            $table->foreignIdFor(Type_animation::class)->constrained;
            $table->timestamps();
        });

        Schema::create('animation_user', function(Blueprint $table) {
            $table->foreignIdFor(\App\Models\Animation::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(\App\Models\User::class)->constrained()->cascadeOnDelete();
            $table->primary(['animation_id', 'user_id']);
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
