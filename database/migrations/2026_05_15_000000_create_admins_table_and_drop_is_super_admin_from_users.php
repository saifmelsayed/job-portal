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
        $adminRole = UserRole::Admin->value;
        $now = now();

        Schema::create('admins', function (Blueprint $table): void {
            $table->foreignId('user_id')->primary()->constrained()->cascadeOnDelete();
            $table->boolean('is_super_admin')->default(false);
            $table->timestamps();
        });

        if (Schema::hasColumn('users', 'is_super_admin')) {
            $rows = DB::table('users')
                ->where('role', $adminRole)
                ->select(['id', 'is_super_admin'])
                ->get();

            foreach ($rows as $u) {
                DB::table('admins')->insert([
                    'user_id' => $u->id,
                    'is_super_admin' => (bool) $u->is_super_admin,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            Schema::table('users', function (Blueprint $table): void {
                $table->dropColumn('is_super_admin');
            });
        } else {
            foreach (DB::table('users')->where('role', $adminRole)->pluck('id') as $userId) {
                DB::table('admins')->insert([
                    'user_id' => $userId,
                    'is_super_admin' => false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('admins')) {
            return;
        }

        if (! Schema::hasColumn('users', 'is_super_admin')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->boolean('is_super_admin')->default(false)->after('role');
            });
        }

        foreach (DB::table('admins')->cursor() as $row) {
            DB::table('users')->where('id', $row->user_id)->update([
                'is_super_admin' => (bool) $row->is_super_admin,
            ]);
        }

        Schema::dropIfExists('admins');
    }
};
