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
            'name' => 'System Administrator',
            'designation' => 'Duty Office Watch',
            'is_active' => true,
            'password' => env('DTS_SEED_ADMIN_PASSWORD', 'Admin@MEPTDI2026'),
        ]);

        if (app()->environment('local')) {
            foreach ([['watch1', 'Watch One'], ['watch2', 'Watch Two']] as [$username, $name]) {
                User::updateOrCreate(['username' => $username], [
                    'rank' => 'SN1',
                    'name' => $name,
                    'designation' => 'Duty Office Watch',
                    'is_active' => true,
                    'password' => env('DTS_SEED_WATCH_PASSWORD', 'Watch@MEPTDI2026'),
                ]);
            }
        }
    }
}
