<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seeds the initial accounts. Change these passwords at first sign-in
     * (Account › Change password) before operational use.
     */
    public function run(): void
    {
        User::updateOrCreate(['username' => 'admin'], [
            'rank' => null,
            'first_name' => 'System',
            'last_name' => 'Administrator',
            'designation' => 'Duty Office Watch',
            'is_active' => true,
            'password' => env('DTS_SEED_ADMIN_PASSWORD', 'Admin@MEPTDI2026'),
        ]);

        if (app()->environment('local')) {
            foreach ([['watch1', 'One'], ['watch2', 'Two']] as [$username, $last]) {
                User::updateOrCreate(['username' => $username], [
                    'rank' => 'SN1',
                    'first_name' => 'Watch',
                    'last_name' => $last,
                    'designation' => 'Duty Office Watch',
                    'is_active' => true,
                    'password' => env('DTS_SEED_WATCH_PASSWORD', 'Watch@MEPTDI2026'),
                ]);
            }
        }
    }
}
