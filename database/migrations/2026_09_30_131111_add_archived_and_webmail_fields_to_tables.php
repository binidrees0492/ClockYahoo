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
        if (Schema::hasTable('notifications')) {
            Schema::table('notifications', function (Blueprint $table) {
                if (!Schema::hasColumn('notifications', 'archived_at')) {
                    $table->timestamp('archived_at')->nullable()->after('read_at');
                }
            });
        }

        if (Schema::hasTable('email_logs')) {
            Schema::table('email_logs', function (Blueprint $table) {
                if (!Schema::hasColumn('email_logs', 'folder')) {
                    $table->string('folder')->default('sent')->after('body'); // inbox, sent, drafts, archived
                }
                if (!Schema::hasColumn('email_logs', 'is_archived')) {
                    $table->boolean('is_archived')->default(false)->after('folder');
                }
                if (!Schema::hasColumn('email_logs', 'attachments')) {
                    $table->json('attachments')->nullable()->after('is_archived');
                }
                if (!Schema::hasColumn('email_logs', 'read_at')) {
                    $table->timestamp('read_at')->nullable()->after('sent_at');
                }
            });
        }

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'font_preference')) {
                    $table->string('font_preference')->nullable()->after('theme_preference');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('notifications')) {
            Schema::table('notifications', function (Blueprint $table) {
                if (Schema::hasColumn('notifications', 'archived_at')) {
                    $table->dropColumn('archived_at');
                }
            });
        }

        if (Schema::hasTable('email_logs')) {
            Schema::table('email_logs', function (Blueprint $table) {
                $cols = array_filter(['folder', 'is_archived', 'attachments', 'read_at'], fn($c) => Schema::hasColumn('email_logs', $c));
                if (!empty($cols)) {
                    $table->dropColumn($cols);
                }
            });
        }

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (Schema::hasColumn('users', 'font_preference')) {
                    $table->dropColumn('font_preference');
                }
            });
        }
    }
};
