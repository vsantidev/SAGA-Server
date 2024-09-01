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
            $table->string('picture')->nullable();
            $table->longText('content');
            $table->boolean('multiple')->default(false);
            $table->longText('remark')->nullable();
            $table->longText('url')->nullable();
            $table->integer('fight')->nullable();
            $table->integer('reflection')->nullable();
            $table->integer('roleplay')->nullable();
            $table->datetime('open_time')->nullable();
            $table->datetime('closed_time')->nullable();
            $table->integer('time')->nullable();
            $table->longText('other_time')->nullable();
            $table->boolean('validate')->default(false);
            $table->integer('capacity')->nullable();
            $table->boolean('registration')->default(true);
            $table->datetime('registration_date')->nullable();
            $table->foreignIdFor(Room::class)->nullable();
            $table->foreignIdFor(User::class)->constrained;
            $table->foreignIdFor(Evenement::class);
            $table->foreignIdFor(Type_animation::class);
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
