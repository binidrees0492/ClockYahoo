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
        // 1. Email logs table
        if (!Schema::hasTable('email_logs')) {
            Schema::create('email_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('recipient');
                $table->string('sender')->nullable();
                $table->string('subject');
                $table->longText('body')->nullable();
                $table->string('status')->default('sent'); // 'sent', 'failed', 'pending'
                $table->text('error_message')->nullable();
                $table->string('mailer_type')->nullable()->default('smtp');
                $table->timestamp('sent_at')->nullable();
                $table->integer('retry_count')->default(0);
                $table->timestamp('last_attempt_at')->nullable();
                $table->timestamps();
            });
        }

        // 2. Add email & theme columns to users table
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'theme_preference')) {
                    $table->string('theme_preference')->nullable()->after('timezone');
                }
                if (!Schema::hasColumn('users', 'use_custom_email')) {
                    $table->boolean('use_custom_email')->default(false)->after('theme_preference');
                }
                if (!Schema::hasColumn('users', 'smtp_host')) {
                    $table->string('smtp_host')->nullable();
                }
                if (!Schema::hasColumn('users', 'smtp_port')) {
                    $table->integer('smtp_port')->nullable();
                }
                if (!Schema::hasColumn('users', 'smtp_username')) {
                    $table->string('smtp_username')->nullable();
                }
                if (!Schema::hasColumn('users', 'smtp_password')) {
                    $table->text('smtp_password')->nullable();
                }
                if (!Schema::hasColumn('users', 'smtp_encryption')) {
                    $table->string('smtp_encryption')->nullable();
                }
                if (!Schema::hasColumn('users', 'smtp_from_address')) {
                    $table->string('smtp_from_address')->nullable();
                }
                if (!Schema::hasColumn('users', 'smtp_from_name')) {
                    $table->string('smtp_from_name')->nullable();
                }
                if (!Schema::hasColumn('users', 'imap_host')) {
                    $table->string('imap_host')->nullable();
                }
                if (!Schema::hasColumn('users', 'imap_port')) {
                    $table->integer('imap_port')->nullable();
                }
                if (!Schema::hasColumn('users', 'imap_username')) {
                    $table->string('imap_username')->nullable();
                }
                if (!Schema::hasColumn('users', 'imap_password')) {
                    $table->text('imap_password')->nullable();
                }
                if (!Schema::hasColumn('users', 'imap_encryption')) {
                    $table->string('imap_encryption')->nullable();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_logs');

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                $columns = [
                    'theme_preference',
                    'use_custom_email',
                    'smtp_host',
                    'smtp_port',
                    'smtp_username',
                    'smtp_password',
                    'smtp_encryption',
                    'smtp_from_address',
                    'smtp_from_name',
                    'imap_host',
                    'imap_port',
                    'imap_username',
                    'imap_password',
                    'imap_encryption',
                ];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('users', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
