<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('yandex_business_id');
            $table->text('yandex_url');
            $table->string('title')->nullable();
            $table->text('address')->nullable();
            $table->decimal('average_rating', 3, 2)->nullable();
            $table->unsignedInteger('rating_count')->default(0);
            $table->unsignedInteger('review_count')->default(0);
            $table->unsignedInteger('parsed_review_count')->default(0);
            $table->string('sync_status')->default('pending');
            $table->text('sync_error')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'yandex_business_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
