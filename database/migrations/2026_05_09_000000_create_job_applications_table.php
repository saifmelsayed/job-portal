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
        Schema::create('job_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_posting_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('status', 32)->default('pending');

            $table->string('job_title');
            $table->longText('job_description');
            $table->longText('job_requirements');
            $table->longText('job_qualification');
            $table->string('job_location');
            $table->string('job_type', 32);

            $table->string('applicant_name');
            $table->string('applicant_email');
            $table->string('applicant_phone', 30);
            $table->string('applicant_linkedin')->nullable();
            $table->string('cv_path', 500);

            $table->timestamps();

            $table->unique(['job_posting_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_applications');
    }
};
