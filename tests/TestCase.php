<?php

namespace Tests;

use App\Models\Organization;
use App\Models\ProfessionalProfile;
use App\Models\User;
use Database\Seeders\PlatformSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Str;

abstract class TestCase extends BaseTestCase
{
    protected function seedPlatform(): void
    {
        (new PlatformSeeder)->run();
    }

    protected function talent(array $profile = []): User
    {
        $user = User::factory()->create();
        $user->forceFill(['is_talent' => true])->save();
        $p = new ProfessionalProfile(array_merge(['headline' => 'Dev', 'bio' => 'Bio', 'primary_role' => 'Engineer'], $profile));
        $p->user_id = $user->id;
        $p->slug = Str::slug($user->name).'-'.$user->id;
        $p->status = $profile['status'] ?? 'approved';
        $p->save();

        return $user->fresh();
    }

    protected function hirer(int $credits = 0): User
    {
        $user = User::factory()->create();
        $user->forceFill(['is_hirer' => true])->save();
        $org = new Organization(['name' => 'Acme '.$user->id, 'country' => 'Nigeria']);
        $org->owner_id = $user->id;
        $org->slug = 'acme-'.$user->id;
        $org->unlock_credits = $credits;
        $org->save();
        $org->members()->attach($user->id, ['role' => 'owner']);

        return $user->fresh();
    }

    protected function staff(string $role = 'super_admin'): User
    {
        $user = User::factory()->create();
        $user->forceFill(['staff_role' => $role])->save();

        return $user;
    }
}
