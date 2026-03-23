<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\AdminUser;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateAdminUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:create';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new admin user for tenant management';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('=== Create Admin User ===');
        $this->info('');

        $name = $this->ask('Name');
        $email = $this->ask('Email');
        $password = $this->secret('Password (min 8 characters)');
        $passwordConfirm = $this->secret('Confirm Password');

        // Validate input
        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ], [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:admin.admin_users,email',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            $this->error('Validation failed:');
            foreach ($validator->errors()->all() as $error) {
                $this->error('- ' . $error);
            }
            return 1;
        }

        if ($password !== $passwordConfirm) {
            $this->error('Passwords do not match!');
            return 1;
        }

        // Create admin user
        $admin = AdminUser::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'is_active' => true,
        ]);

        $this->info('');
        $this->info('Admin user created successfully!');
        $this->info('');
        $this->table(
            ['ID', 'Name', 'Email', 'Status'],
            [[$admin->id, $admin->name, $admin->email, 'Active']]
        );
        $this->info('');
        $this->info('You can now login at: ' . url('/admin/login'));

        return 0;
    }
}
