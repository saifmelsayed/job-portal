<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    /** @var list<string> */
    private const DROP_COLUMNS = [
        'job_title',
        'job_description',
        'job_requirements',
        'job_qualification',
        'job_location',
        'job_type',
        'job_approved_disability',
        'job_skills',
        'job_category',
        'applicant_name',
        'applicant_email',
        'applicant_phone',
        'applicant_linkedin',
        'cv_path',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('job_applications')) {
            return;
        }

        $this->deleteOrphanedApplicationCvFiles();

        $existing = array_values(array_filter(
            self::DROP_COLUMNS,
            static fn (string $column): bool => Schema::hasColumn('job_applications', $column),
        ));

        if ($existing === []) {
            return;
        }

        Schema::table('job_applications', function (Blueprint $table) use ($existing): void {
            $table->dropColumn($existing);
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('job_applications')) {
            return;
        }

        Schema::table('job_applications', function (Blueprint $table): void {
            if (! Schema::hasColumn('job_applications', 'job_title')) {
                $table->string('job_title')->nullable()->after('status');
            }
            if (! Schema::hasColumn('job_applications', 'job_description')) {
                $table->longText('job_description')->nullable()->after('job_title');
            }
            if (! Schema::hasColumn('job_applications', 'job_requirements')) {
                $table->longText('job_requirements')->nullable()->after('job_description');
            }
            if (! Schema::hasColumn('job_applications', 'job_qualification')) {
                $table->longText('job_qualification')->nullable()->after('job_requirements');
            }
            if (! Schema::hasColumn('job_applications', 'job_location')) {
                $table->string('job_location')->nullable()->after('job_qualification');
            }
            if (! Schema::hasColumn('job_applications', 'job_type')) {
                $table->string('job_type', 32)->nullable()->after('job_location');
            }
            if (! Schema::hasColumn('job_applications', 'job_approved_disability')) {
                $table->json('job_approved_disability')->nullable()->after('job_type');
            }
            if (! Schema::hasColumn('job_applications', 'job_skills')) {
                $table->json('job_skills')->nullable()->after('job_approved_disability');
            }
            if (! Schema::hasColumn('job_applications', 'job_category')) {
                $table->string('job_category')->nullable()->after('job_skills');
            }
            if (! Schema::hasColumn('job_applications', 'applicant_name')) {
                $table->string('applicant_name')->nullable()->after('job_category');
            }
            if (! Schema::hasColumn('job_applications', 'applicant_email')) {
                $table->string('applicant_email')->nullable()->after('applicant_name');
            }
            if (! Schema::hasColumn('job_applications', 'applicant_phone')) {
                $table->string('applicant_phone', 30)->nullable()->after('applicant_email');
            }
            if (! Schema::hasColumn('job_applications', 'applicant_linkedin')) {
                $table->string('applicant_linkedin')->nullable()->after('applicant_phone');
            }
            if (! Schema::hasColumn('job_applications', 'cv_path')) {
                $table->string('cv_path', 500)->nullable()->after('applicant_linkedin');
            }
        });
    }

    private function deleteOrphanedApplicationCvFiles(): void
    {
        if (! Schema::hasColumn('job_applications', 'cv_path')) {
            return;
        }

        $disk = Storage::disk('public');

        foreach (DB::table('job_applications')->whereNotNull('cv_path')->cursor() as $application) {
            $applicationCv = is_string($application->cv_path) ? trim($application->cv_path) : '';
            if ($applicationCv === '' || str_contains($applicationCv, '..')) {
                continue;
            }

            $profileCv = null;
            if (Schema::hasTable('job_seeker_profiles')) {
                $profileCv = DB::table('job_seeker_profiles')
                    ->where('user_id', $application->user_id)
                    ->value('cv_path');
            }

            $profileCv = is_string($profileCv) ? trim($profileCv) : '';
            if ($applicationCv === $profileCv) {
                continue;
            }

            if ($disk->exists($applicationCv)) {
                $disk->delete($applicationCv);
            }
        }
    }
};
