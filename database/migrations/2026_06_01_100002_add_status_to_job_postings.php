<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('job_postings') || Schema::hasColumn('job_postings', 'status')) {
            return;
        }

        Schema::table('job_postings', function (Blueprint $table): void {
            $table->string('status', 32)->default('active')->after('user_id');
        });

        DB::table('job_postings')->update(['status' => 'active']);
    }

    public function down(): void
    {
        if (! Schema::hasTable('job_postings') || ! Schema::hasColumn('job_postings', 'status')) {
            return;
        }

        Schema::table('job_postings', function (Blueprint $table): void {
            $table->dropColumn('status');
        });
    }
};
