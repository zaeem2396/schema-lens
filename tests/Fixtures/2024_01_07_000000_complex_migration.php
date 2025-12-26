<?php

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
        // Add columns
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar')->nullable();
            $table->json('settings')->nullable();
        });

        // Drop column (destructive)
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('remember_token');
        });

        // Rename column (destructive)
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('email', 'email_address');
        });

        // Add index
        Schema::table('users', function (Blueprint $table) {
            $table->index('avatar');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['avatar']);
            $table->renameColumn('email_address', 'email');
            $table->rememberToken();
            $table->dropColumn(['avatar', 'settings']);
        });
    }
};
