<?php

use Botble\ACL\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('bb_comments', function (Blueprint $table) {
            $table->string('user_type', 255)->default(addslashes(User::class));
        });

        Schema::table('bb_comment_users', function (Blueprint $table) {
            $table->dropColumn('user_type');
            $table->renameColumn('name', 'first_name');
        });

        Schema::table('bb_comment_users', function (Blueprint $table) {
            $table->string('last_name', 60);
        });

        Schema::table('bb_comment_likes', function (Blueprint $table) {
            $table->string('user_type', 255)->default(addslashes(User::class));
        });

        Schema::table('bb_comment_recommends', function (Blueprint $table) {
            $table->string('user_type', 255)->default(addslashes(User::class));
        });
    }

    public function down(): void
    {
        Schema::table('bb_comments', function (Blueprint $table) {
            $table->dropColumn('user_type');
        });

        Schema::table('bb_comment_users', function (Blueprint $table) {
            $table->string('user_type', 255)->default(addslashes(User::class));
            $table->renameColumn('first_name', 'name');
        });

        Schema::table('bb_comment_users', function (Blueprint $table) {
            $table->dropColumn('last_name');
        });

        Schema::table('bb_comment_likes', function (Blueprint $table) {
            $table->dropColumn('user_type');
        });

        Schema::table('bb_comment_recommends', function (Blueprint $table) {
            $table->dropColumn('user_type');
        });
    }
};
