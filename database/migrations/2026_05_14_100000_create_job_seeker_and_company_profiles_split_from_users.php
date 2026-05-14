<?php

use App\Enums\UserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $jobSeeker = UserRole::JobSeeker->value;
        $company = UserRole::Company->value;
        $now = now();

        Schema::create('job_seeker_profiles', function (Blueprint $table) {
            $table->foreignId('user_id')->primary()->constrained()->cascadeOnDelete();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('full_name')->nullable();
            $table->string('cv_path', 500)->nullable();
            $table->string('gender', 20)->nullable();
            $table->string('disability_type', 100)->nullable();
            $table->timestamps();
        });

        Schema::create('company_profiles', function (Blueprint $table) {
            $table->foreignId('user_id')->primary()->constrained()->cascadeOnDelete();
            $table->string('company_name')->nullable();
            $table->string('industry', 100)->nullable();
            $table->string('company_size', 50)->nullable();
            $table->text('disability_support_policy')->nullable();
            $table->timestamps();
        });

        $rows = DB::table('users')
            ->where('role', $jobSeeker)
            ->select([
                'id',
                'first_name',
                'last_name',
                'full_name',
                'cv_path',
                'gender',
            ])
            ->get();

        foreach ($rows as $u) {
            DB::table('job_seeker_profiles')->insert([
                'user_id' => $u->id,
                'first_name' => $u->first_name,
                'last_name' => $u->last_name,
                'full_name' => $u->full_name,
                'cv_path' => $u->cv_path,
                'gender' => $u->gender,
                'disability_type' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $rows = DB::table('users')
            ->where('role', $company)
            ->select([
                'id',
                'company_name',
                'industry',
                'company_size',
            ])
            ->get();

        foreach ($rows as $u) {
            DB::table('company_profiles')->insert([
                'user_id' => $u->id,
                'company_name' => $u->company_name,
                'industry' => $u->industry,
                'company_size' => $u->company_size,
                'disability_support_policy' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        DB::table('users')
            ->whereIn('role', [$jobSeeker, $company])
            ->update([
                'first_name' => null,
                'last_name' => null,
            ]);

        $drop = array_filter([
            Schema::hasColumn('users', 'full_name') ? 'full_name' : null,
            Schema::hasColumn('users', 'cv_path') ? 'cv_path' : null,
            Schema::hasColumn('users', 'company_name') ? 'company_name' : null,
            Schema::hasColumn('users', 'industry') ? 'industry' : null,
            Schema::hasColumn('users', 'company_size') ? 'company_size' : null,
            Schema::hasColumn('users', 'gender') ? 'gender' : null,
        ]);

        if ($drop !== []) {
            Schema::table('users', function (Blueprint $table) use ($drop): void {
                $table->dropColumn($drop);
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('job_seeker_profiles')) {
            return;
        }

        if (! Schema::hasColumn('users', 'full_name')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->string('full_name')->nullable()->after('last_name');
            });
        }
        if (! Schema::hasColumn('users', 'cv_path')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->string('cv_path', 500)->nullable();
            });
        }
        if (! Schema::hasColumn('users', 'company_name')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->string('company_name')->nullable();
            });
        }
        if (! Schema::hasColumn('users', 'industry')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->string('industry', 100)->nullable();
            });
        }
        if (! Schema::hasColumn('users', 'company_size')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->string('company_size', 50)->nullable();
            });
        }
        if (! Schema::hasColumn('users', 'gender')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->string('gender', 20)->nullable();
            });
        }

        foreach (DB::table('job_seeker_profiles')->cursor() as $p) {
            DB::table('users')->where('id', $p->user_id)->update([
                'first_name' => $p->first_name,
                'last_name' => $p->last_name,
                'full_name' => $p->full_name,
                'cv_path' => $p->cv_path,
                'gender' => $p->gender,
            ]);
        }

        foreach (DB::table('company_profiles')->cursor() as $c) {
            DB::table('users')->where('id', $c->user_id)->update([
                'company_name' => $c->company_name,
                'industry' => $c->industry,
                'company_size' => $c->company_size,
            ]);
        }

        Schema::dropIfExists('company_profiles');
        Schema::dropIfExists('job_seeker_profiles');
    }
};
