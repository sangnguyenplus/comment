<?php

use Botble\ACL\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('bb_comments', function (Blueprint $table) {
            $table->id();
            $table->longText('comment')->nullable();
            $table->foreignId('reference_id');
            $table->string('reference_type', 120);
            $table->string('ip_address', 39)->nullable();
            $table->foreignId('user_id')->nullable();
            $table->string('status', 60)->default('published');
            $table->integer('like_count')->default(0);
            $table->integer('reply_count')->default(0);
            $table->foreignId('parent_id')->default(0);
            $table->timestamps();
        });

        Schema::create('bb_comment_users', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('email')->unique();
            $table->string('password');
            $table->string('user_type', 255)->default(addslashes(User::class));
            $table->foreignId('avatar_id')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('bb_comment_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comment_id')->index();
            $table->foreignId('user_id')->index();
            $table->timestamps();
        });

        Schema::create('bb_comment_recommends', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reference_id');
            $table->string('reference_type', 120);
            $table->foreignId('user_id')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('bb_comments');
        Schema::dropIfExists('bb_comment_users');
        Schema::dropIfExists('bb_comment_likes');
        Schema::dropIfExists('bb_comment_recommends');
    }
};
