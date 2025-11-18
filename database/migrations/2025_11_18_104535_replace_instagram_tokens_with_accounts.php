<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // If old tokens table exists, drop it
        if (Schema::hasTable('instagram_tokens')) {
            Schema::dropIfExists('instagram_tokens');
        }

        // Create new instagram_accounts table
        Schema::create('instagram_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable(); // link to your local user (if any)
            $table->string('instagram_user_id')->unique();
            $table->string('username')->nullable();
            $table->text('access_token');
            $table->text('refresh_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->json('profile_json')->nullable(); // store profile snapshot if desired
            $table->timestamps();

            // If you have users table, add foreign key (optional)
            // $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instagram_accounts');

        // optionally recreate old tokens table (not necessary)
        Schema::create('instagram_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('instagram_user_id')->unique();
            $table->text('access_token');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }
};
