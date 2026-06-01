<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('skills')) {
            Schema::create('skills', function (Blueprint $table): void {
                $table->id();
                $table->string('name', 100)->unique();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('user_skills') && Schema::hasColumn('user_skills', 'skill_id')) {
            Schema::dropIfExists('user_skills_legacy');

            return;
        }

        $sourceTable = null;
        if (Schema::hasTable('user_skills') && Schema::hasColumn('user_skills', 'name')) {
            $sourceTable = 'user_skills';
        } elseif (Schema::hasTable('user_skills_legacy') && Schema::hasColumn('user_skills_legacy', 'name')) {
            $sourceTable = 'user_skills_legacy';
        }

        if ($sourceTable === null) {
            return;
        }

        $now = now();
        $skillIdsByKey = [];

        foreach (DB::table($sourceTable)->select('name')->distinct()->orderBy('name')->cursor() as $row) {
            $name = is_string($row->name) ? trim($row->name) : '';
            if ($name === '') {
                continue;
            }
            $name = mb_substr($name, 0, 100);
            $key = mb_strtolower($name);
            if (isset($skillIdsByKey[$key])) {
                continue;
            }

            $existingId = DB::table('skills')->whereRaw('LOWER(name) = ?', [$key])->value('id');
            if ($existingId !== null) {
                $skillIdsByKey[$key] = (int) $existingId;

                continue;
            }

            $id = DB::table('skills')->insertGetId([
                'name' => $name,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $skillIdsByKey[$key] = $id;
        }

        $pivotRows = [];
        $seenPairs = [];

        foreach (DB::table($sourceTable)->orderBy('user_id')->orderBy('sort_order')->orderBy('id')->cursor() as $row) {
            $name = is_string($row->name) ? trim($row->name) : '';
            if ($name === '') {
                continue;
            }
            $key = mb_strtolower(mb_substr($name, 0, 100));
            $skillId = $skillIdsByKey[$key] ?? null;
            if ($skillId === null) {
                continue;
            }

            $pairKey = $row->user_id.':'.$skillId;
            if (isset($seenPairs[$pairKey])) {
                continue;
            }
            $seenPairs[$pairKey] = true;

            $pivotRows[] = [
                'user_id' => $row->user_id,
                'skill_id' => $skillId,
                'sort_order' => (int) $row->sort_order,
                'created_at' => $row->created_at ?? $now,
                'updated_at' => $row->updated_at ?? $now,
            ];
        }

        Schema::dropIfExists('user_skills');
        Schema::dropIfExists('user_skills_legacy');

        Schema::create('user_skills', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('skill_id');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('user_id', 'user_skill_pivot_user_id_foreign')
                ->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('skill_id', 'user_skill_pivot_skill_id_foreign')
                ->references('id')->on('skills')->cascadeOnDelete();

            $table->unique(['user_id', 'skill_id']);
            $table->index('user_id');
        });

        if ($pivotRows !== []) {
            foreach (array_chunk($pivotRows, 500) as $chunk) {
                DB::table('user_skills')->insert($chunk);
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('user_skills') || ! Schema::hasColumn('user_skills', 'skill_id')) {
            return;
        }

        $now = now();

        $rows = DB::table('user_skills')
            ->join('skills', 'skills.id', '=', 'user_skills.skill_id')
            ->orderBy('user_skills.user_id')
            ->orderBy('user_skills.sort_order')
            ->orderBy('user_skills.id')
            ->select([
                'user_skills.user_id',
                'skills.name',
                'user_skills.sort_order',
                'user_skills.created_at',
                'user_skills.updated_at',
            ])
            ->get();

        Schema::dropIfExists('user_skills');

        Schema::create('user_skills', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('name', 100);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('user_id', 'user_skills_user_id_foreign')
                ->references('id')->on('users')->cascadeOnDelete();

            $table->unique(['user_id', 'name']);
            $table->index('user_id');
        });

        foreach ($rows as $row) {
            DB::table('user_skills')->insert([
                'user_id' => $row->user_id,
                'name' => $row->name,
                'sort_order' => $row->sort_order,
                'created_at' => $row->created_at ?? $now,
                'updated_at' => $row->updated_at ?? $now,
            ]);
        }

        Schema::dropIfExists('skills');
    }
};
