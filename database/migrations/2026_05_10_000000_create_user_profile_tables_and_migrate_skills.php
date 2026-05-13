<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'name']);
            $table->index('user_id');
        });

        Schema::create('user_educations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('institution');
            $table->string('degree')->nullable();
            $table->string('field_of_study')->nullable();
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->text('details')->nullable();
            $table->timestamps();

            $table->index('user_id');
        });

        Schema::create('user_experiences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('company_name');
            $table->string('title');
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index('user_id');
        });

        Schema::create('user_certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('issuer')->nullable();
            $table->date('issued_at')->nullable();
            $table->string('credential_url', 2048)->nullable();
            $table->timestamps();

            $table->index('user_id');
        });

        if (! Schema::hasColumn('users', 'skills')) {
            return;
        }

        $now = now();

        $users = DB::table('users')->select(['id', 'skills'])->cursor();

        foreach ($users as $user) {
            $skillNames = $this->decodeSkillsToList($user->skills);
            $seen = [];
            $order = 0;
            foreach ($skillNames as $rawName) {
                $name = mb_substr(trim($rawName), 0, 100);
                if ($name === '') {
                    continue;
                }
                $key = mb_strtolower($name);
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;

                DB::table('user_skills')->insert([
                    'user_id' => $user->id,
                    'name' => $name,
                    'sort_order' => $order,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $order++;
            }
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('skills');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('users', 'skills')) {
            Schema::table('users', function (Blueprint $table) {
                $table->json('skills')->nullable();
            });
        }

        if (Schema::hasTable('user_skills') && Schema::hasColumn('users', 'skills')) {
            $skillsByUser = [];

            foreach (DB::table('user_skills')->orderBy('user_id')->orderBy('sort_order')->orderBy('id')->cursor() as $row) {
                $uid = (int) $row->user_id;
                if (! isset($skillsByUser[$uid])) {
                    $skillsByUser[$uid] = [];
                }
                $skillsByUser[$uid][] = $row->name;
            }

            foreach ($skillsByUser as $userId => $names) {
                DB::table('users')->where('id', $userId)->update([
                    'skills' => json_encode(array_values($names)),
                ]);
            }
        }

        Schema::dropIfExists('user_certificates');
        Schema::dropIfExists('user_experiences');
        Schema::dropIfExists('user_educations');
        Schema::dropIfExists('user_skills');
    }

    /**
     * @return list<string>
     */
    private function decodeSkillsToList(mixed $skills): array
    {
        if ($skills === null || $skills === '') {
            return [];
        }

        if (is_array($skills)) {
            return array_values(array_filter(
                array_map(fn ($s) => is_string($s) ? $s : '', $skills),
                fn (string $s) => $s !== ''
            ));
        }

        if (is_string($skills)) {
            $decoded = json_decode($skills, true);

            if (! is_array($decoded)) {
                return [];
            }

            return array_values(array_filter(
                array_map(fn ($s) => is_string($s) ? $s : '', $decoded),
                fn (string $s) => $s !== ''
            ));
        }

        return [];
    }
};
