<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Runs when an older 2026_04_28_100000 migration (with separate profile tables) already completed.
 * New installs get columns from 100000; this migration no-ops if `full_name` already exists.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'full_name')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name')->nullable()->change();
            $table->string('last_name')->nullable()->change();
            $table->string('full_name')->nullable()->after('last_name');
            $table->json('skills')->nullable();
            $table->string('cv_path', 500)->nullable();
            $table->string('company_name')->nullable();
            $table->string('industry', 100)->nullable();
            $table->string('company_size', 50)->nullable();
        });

        if (Schema::hasTable('job_seeker_profiles')) {
            $profiles = DB::table('job_seeker_profiles')->get();
            foreach ($profiles as $p) {
                $skills = $p->skills;
                if (is_string($skills)) {
                    $skills = json_decode($skills, true) ?? [];
                }
                DB::table('users')->where('id', $p->user_id)->update([
                    'full_name' => $p->full_name,
                    'skills' => json_encode($skills),
                    'cv_path' => $p->cv_path,
                ]);
            }
            Schema::dropIfExists('job_seeker_profiles');
        }

        if (Schema::hasTable('companies')) {
            $rows = DB::table('companies')->get();
            foreach ($rows as $c) {
                DB::table('users')->where('id', $c->user_id)->update([
                    'company_name' => $c->name,
                    'industry' => $c->industry,
                    'company_size' => $c->company_size,
                ]);
            }
            Schema::dropIfExists('companies');
        }
    }

    public function down(): void
    {
        //
    }
};
