<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Splits the single "name" field into first name, middle initial and last name.
 * The full "name" column is kept and maintained automatically by the User model,
 * so sorting and searching by name keep working.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name', 100)->nullable()->after('rank');
            $table->string('middle_initial', 5)->nullable()->after('first_name');
            $table->string('last_name', 100)->nullable()->after('middle_initial');
        });

        // Best-effort split of existing names, e.g. "Ian Russel U Pascual"
        // → first "Ian Russel", middle initial "U", last "Pascual".
        DB::table('users')->orderBy('id')->each(function ($user) {
            $parts = preg_split('/\s+/', trim((string) $user->name)) ?: [];
            $last = count($parts) > 1 ? array_pop($parts) : '';
            $middle = '';
            if (count($parts) > 1 && preg_match('/^[A-Za-z]\.?$/', end($parts))) {
                $middle = rtrim(array_pop($parts), '.');
            }

            DB::table('users')->where('id', $user->id)->update([
                'first_name' => implode(' ', $parts) ?: $user->name,
                'middle_initial' => $middle !== '' ? strtoupper($middle) : null,
                'last_name' => $last,
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['first_name', 'middle_initial', 'last_name']);
        });
    }
};
