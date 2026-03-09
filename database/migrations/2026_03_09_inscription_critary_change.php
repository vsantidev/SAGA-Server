<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inscriptions', function (Blueprint $table) {
            $table->unsignedTinyInteger('weight')->default(1)->after('animation_id');
            $table->string('status')->default('en_attente')->after('weight');
            $table->timestamp('registered_at')->nullable()->after('status');
            
            $table->unique(['user_id', 'animation_id']);
        });
    }

    public function down(): void
    {
        Schema::table('inscriptions', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'animation_id']);
            $table->dropColumn(['weight', 'status', 'inscrit_le']);
        });
    }
};