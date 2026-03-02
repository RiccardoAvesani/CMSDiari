<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // se presente, rinomina 'token' -> 'access_token' compatibile con SQLite
        if (Schema::hasColumn('invitations', 'token')) {
            if (config('database.default') === 'sqlite') {
                DB::statement('ALTER TABLE invitations RENAME COLUMN token TO access_token');
            } else {
                Schema::table('invitations', function (Blueprint $table) {
                    $table->renameColumn('token', 'access_token');
                });
            }
        }

        Schema::table('invitations', function (Blueprint $table) {
            if (!Schema::hasColumn('invitations', 'open_token')) {
                // pixel tracking: posizionare 'open_token' subito dopo 'access_token' (se possibile)
                $table->string('open_token', 128)->nullable()->after('access_token');
                $table->unique('open_token');
            }

            // received tracking
            $table->dateTime('received_at')->nullable()->after('sent_at');
            $table->string('received_via', 16)->nullable()->after('received_at'); // 'pixel' | 'link'
            $table->index(['status', 'expires_at']);
        });

        Schema::table('invitations', function (Blueprint $table) {
            if (! Schema::hasColumn('invitations', 'created_by')) {
                $table->foreignId('created_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete()
                    ->after('id');
            }

            if (! Schema::hasColumn('invitations', 'updated_by')) {
                $table->foreignId('updated_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete()
                    ->after('created_by');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invitations', function (Blueprint $table) {
            $table->dropUnique(['open_token']);
            $table->dropColumn('open_token');
        });

        // se presente, ripristina il nome originario della colonna compatibile con SQLite
        if (Schema::hasColumn('invitations', 'access_token')) {
            if (config('database.default') === 'sqlite') {
                DB::statement('ALTER TABLE invitations RENAME COLUMN access_token TO token');
            } else {
                Schema::table('invitations', function (Blueprint $table) {
                    $table->renameColumn('access_token', 'token');
                });
            }
        }

        Schema::table('invitations', function (Blueprint $table) {
            $table->dropColumn(['received_at', 'received_via']);
            $table->dropIndex(['status', 'expires_at']);
        });

        Schema::table('invitations', function (Blueprint $table) {
            // Drop FK prima delle colonne (pattern standard)
            if (Schema::hasColumn('invitations', 'updated_by')) {
                $table->dropConstrainedForeignId('updated_by');
            }

            if (Schema::hasColumn('invitations', 'created_by')) {
                $table->dropConstrainedForeignId('created_by');
            }
        });
    }
};
