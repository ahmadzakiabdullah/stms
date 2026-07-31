<?php

namespace App\Console\Commands;

use App\Models\Organization;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class CreateSuperAdmin extends Command
{
    protected $signature = 'stms:create-super-admin
        {email : Administrator email address}
        {organization : Organization slug}';

    protected $description = 'Securely provision the initial STMS super-admin without seeded credentials';

    public function handle(UserService $users): int
    {
        $email = mb_strtolower(trim((string) $this->argument('email')));
        $organizationSlug = trim((string) $this->argument('organization'));
        $name = trim((string) $this->ask('Administrator name'));
        $password = (string) $this->secret('Password (minimum 12 characters)');
        $confirmation = (string) $this->secret('Confirm password');

        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $confirmation,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'confirmed', Password::min(12)],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $organization = Organization::withoutGlobalScopes()
            ->where('slug', $organizationSlug)
            ->first();

        if (! $organization) {
            $this->error("Organization [{$organizationSlug}] does not exist.");

            return self::FAILURE;
        }

        if (User::withTrashed()->where('email', $email)->exists()) {
            $this->error("A user with email [{$email}] already exists.");

            return self::FAILURE;
        }

        $role = Role::where('name', 'super-admin')->where('guard_name', 'web')->first();

        if (! $role) {
            $this->error('The super-admin role is missing. Run the production-safe DatabaseSeeder first.');

            return self::FAILURE;
        }

        DB::transaction(function () use ($users, $name, $email, $password, $organization, $role): void {
            $users->createUser([
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'organization_id' => $organization->id,
                'roles' => [$role->id],
            ]);
        });

        $this->info("Super-admin [{$email}] created for [{$organization->name}].");

        return self::SUCCESS;
    }
}
