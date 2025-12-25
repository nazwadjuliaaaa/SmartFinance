<?php

namespace App\Providers;

use App\Models\User;
use App\Services\SupabaseService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Support\Facades\Hash;

class SupabaseUserProvider implements UserProvider
{
    protected SupabaseService $supabase;

    public function __construct(SupabaseService $supabase)
    {
        $this->supabase = $supabase;
    }

    public function retrieveById($identifier): ?Authenticatable
    {
        $userData = $this->supabase->find('users', $identifier);
        
        if (!$userData) {
            return null;
        }

        return $this->mapToUser($userData);
    }

    public function retrieveByToken($identifier, $token): ?Authenticatable
    {
        $users = $this->supabase->select('users', ['*'], [
            'id' => "eq.{$identifier}",
            'remember_token' => "eq.{$token}"
        ], 1);

        if (empty($users)) {
            return null;
        }

        return $this->mapToUser($users[0]);
    }

    public function updateRememberToken(Authenticatable $user, $token): void
    {
        $this->supabase->update('users', ['remember_token' => $token], ['id' => "eq.{$user->getAuthIdentifier()}"]);
    }

    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        // Remove password from credentials for searching
        $searchCredentials = array_filter($credentials, fn($key) => $key !== 'password', ARRAY_FILTER_USE_KEY);
        
        if (empty($searchCredentials)) {
            return null;
        }

        // Build filter
        $filters = [];
        foreach ($searchCredentials as $key => $value) {
            $filters[$key] = "eq.{$value}";
        }

        $users = $this->supabase->select('users', ['*'], $filters, 1);

        if (empty($users)) {
            return null;
        }

        return $this->mapToUser($users[0]);
    }

    public function validateCredentials(Authenticatable $user, array $credentials): bool
    {
        return Hash::check($credentials['password'], $user->getAuthPassword());
    }

    public function rehashPasswordIfRequired(Authenticatable $user, array $credentials, bool $force = false): void
    {
        if (!Hash::needsRehash($user->getAuthPassword()) && !$force) {
            return;
        }

        $this->supabase->update('users', [
            'password' => Hash::make($credentials['password'])
        ], ['id' => "eq.{$user->getAuthIdentifier()}"]);
    }

    protected function mapToUser(array $data): User
    {
        $user = new User();
        $user->id = $data['id'];
        $user->name = $data['name'];
        $user->username = $data['username'];
        $user->email = $data['email'];
        $user->password = $data['password'];
        $user->remember_token = $data['remember_token'] ?? null;
        $user->profile_photo = $data['profile_photo'] ?? null;
        $user->exists = true;
        
        return $user;
    }
}
