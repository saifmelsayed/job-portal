<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_postings', function (Blueprint $table): void {
            $table->string('category')->nullable()->after('approved_disability');
            $table->json('skills')->nullable()->after('category');
        });

        Schema::table('job_applications', function (Blueprint $table): void {
            $table->json('job_skills')->nullable()->after('job_approved_disability');
            $table->string('job_category')->nullable()->after('job_skills');
        });
    }

    public function down(): void
    {
        Schema::table('job_applications', function (Blueprint $table): void {
            $table->dropColumn(['job_skills', 'job_category']);
        });

        Schema::table('job_postings', function (Blueprint $table): void {
            $table->dropColumn(['skills', 'category']);
        });
    }
};
