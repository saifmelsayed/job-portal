<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_profiles', function (Blueprint $table): void {
            $table->text('overview')->nullable()->after('disability_support_policy');
            $table->string('facebook_url', 2048)->nullable()->after('overview');
            $table->string('x_url', 2048)->nullable()->after('facebook_url');
            $table->string('linkedin_url', 2048)->nullable()->after('x_url');
            $table->string('instagram_url', 2048)->nullable()->after('linkedin_url');
        });
    }

    public function down(): void
    {
        Schema::table('company_profiles', function (Blueprint $table): void {
            $table->dropColumn([
                'overview',
                'facebook_url',
                'x_url',
                'linkedin_url',
                'instagram_url',
            ]);
        });
    }
};
