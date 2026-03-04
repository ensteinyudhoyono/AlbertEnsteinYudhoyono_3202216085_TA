<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class SetupSecurityNotifications extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'security:setup {--create-role} {--create-user} {--phone=} {--name=} {--email=}';

    /**
     * The console command description.
     */
    protected $description = 'Setup security notification system and create necessary users/roles';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚨 SETTING UP SECURITY NOTIFICATION SYSTEM');
        $this->info('==========================================');

        // 1. Create Security Role
        if ($this->option('create-role')) {
            $this->createSecurityRole();
        }

        // 2. Create Security User
        if ($this->option('create-user')) {
            $this->createSecurityUser();
        }

        // 3. Check existing security users
        $this->checkSecurityUsers();

        // 4. Test configuration
        $this->testConfiguration();

        $this->info("\n✅ Security notification setup completed!");
        $this->info("💡 Next steps:");
        $this->info("1. Ensure GOWA server is running");
        $this->info("2. Start queue worker: php artisan queue:work");
        $this->info("3. Test notifications: php artisan rent:test-security-notification --rent_id=1");
    }

    private function createSecurityRole()
    {
        $this->info("\n1️⃣ Creating Security Role...");
        
        $role = Role::firstOrCreate(
            ['id' => 2],
            ['name' => 'Security', 'created_at' => now(), 'updated_at' => now()]
        );
        
        $this->info("✅ Security role created/found with ID: {$role->id}");
    }

    private function createSecurityUser()
    {
        $this->info("\n2️⃣ Creating Security User...");
        
        $name = $this->option('name') ?: $this->ask('Enter security user name', 'Satpam');
        $email = $this->option('email') ?: $this->ask('Enter security user email', 'satpam@example.com');
        $phone = $this->option('phone') ?: $this->ask('Enter security user phone (6281234567890)', '6281234567890');
        
        // Validate phone format
        if (!preg_match('/^628\d{8,11}$/', $phone)) {
            $this->error("❌ Invalid phone format. Use format: 6281234567890");
            return;
        }
        
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'role_id' => 2,
            'status' => 'active',
            'password' => Hash::make('password123'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $this->info("✅ Security user created:");
        $this->info("   Name: {$user->name}");
        $this->info("   Email: {$user->email}");
        $this->info("   Phone: {$user->phone}");
        $this->info("   Password: password123 (please change)");
    }

    private function checkSecurityUsers()
    {
        $this->info("\n3️⃣ Checking Security Users...");
        
        $securityUsers = User::where('role_id', 2)->get();
        
        if ($securityUsers->count() == 0) {
            $this->warn("⚠️ No security users found!");
            $this->info("💡 Run with --create-user to create one");
        } else {
            $this->info("✅ Found {$securityUsers->count()} security user(s):");
            
            $this->table(
                ['ID', 'Name', 'Email', 'Phone', 'Status'],
                $securityUsers->map(function ($user) {
                    return [
                        $user->id,
                        $user->name,
                        $user->email,
                        $user->phone ?? 'NO PHONE',
                        $user->status,
                    ];
                })
            );
        }
    }

    private function testConfiguration()
    {
        $this->info("\n4️⃣ Testing Configuration...");
        
        // Check if GOWA is accessible
        try {
            $response = file_get_contents('http://localhost:3000');
            $this->info("✅ GOWA server is accessible");
        } catch (\Exception $e) {
            $this->error("❌ GOWA server is not accessible: " . $e->getMessage());
            $this->warn("💡 Please start GOWA server first");
        }
        
        // Check queue configuration
        $queueDriver = config('queue.default');
        $this->info("✅ Queue driver: {$queueDriver}");
        
        // Check notification channels
        $channels = ['database', 'whatsapp'];
        $this->info("✅ Notification channels: " . implode(', ', $channels));
    }
}

