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
        Schema::table('job_postings', function (Blueprint $table) {
            $table->json('approved_disability')->nullable()->after('type');
        });

        Schema::table('job_applications', function (Blueprint $table) {
            $table->json('job_approved_disability')->nullable()->after('job_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            $table->dropColumn('job_approved_disability');
        });

        Schema::table('job_postings', function (Blueprint $table) {
            $table->dropColumn('approved_disability');
        });
    }
};
