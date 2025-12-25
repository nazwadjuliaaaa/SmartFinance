<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Services\SupabaseService;
use App\Models\User;

class AuthController extends Controller
{
    protected SupabaseService $supabase;

    public function __construct(SupabaseService $supabase)
    {
        $this->supabase = $supabase;
    }

    public function showLogin()
    {
        return view('login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        // Fetch user from Supabase by username
        $users = $this->supabase->select('users', ['*'], ['username' => 'eq.' . $request->username], 1);
        
        if (empty($users)) {
            return back()->withErrors(['username' => 'The provided credentials do not match our records.']);
        }

        $userData = $users[0];
        
        // Verify password
        if (!Hash::check($request->password, $userData['password'])) {
            return back()->withErrors(['username' => 'The provided credentials do not match our records.']);
        }

        // Create local user instance for Auth facade
        $user = new User();
        $user->id = $userData['id'];
        $user->name = $userData['name'];
        $user->username = $userData['username'];
        $user->email = $userData['email'];
        $user->password = $userData['password'];
        $user->exists = true; // Mark as existing to bypass save

        // Login
        Auth::login($user);
        $request->session()->regenerate();
        
        // Store user data in session for later use
        session(['supabase_user' => $userData]);
        
        return redirect()->intended(route('dashboard'));
    }

    public function showRegister()
    {
        return view('register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'username' => 'required',
            'password' => 'required|min:6',
        ]);

        // Check if email already exists in Supabase
        $existingEmail = $this->supabase->select('users', ['id'], ['email' => 'eq.' . $request->email], 1);
        if (!empty($existingEmail)) {
            return back()->withErrors(['email' => 'Email sudah terdaftar.'])->withInput();
        }

        // Check if username already exists in Supabase
        $existingUsername = $this->supabase->select('users', ['id'], ['username' => 'eq.' . $request->username], 1);
        if (!empty($existingUsername)) {
            return back()->withErrors(['username' => 'Username sudah terdaftar.'])->withInput();
        }

        // Insert new user to Supabase
        $newUser = $this->supabase->insert('users', [
            'name' => $request->username,
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        if (!$newUser) {
            return back()->withErrors(['email' => 'Gagal mendaftarkan user. Silakan coba lagi.'])->withInput();
        }

        // Create local user instance for Auth facade
        $user = new User();
        $user->id = $newUser['id'];
        $user->name = $newUser['name'];
        $user->username = $newUser['username'];
        $user->email = $newUser['email'];
        $user->password = $newUser['password'];
        $user->exists = true;

        Auth::login($user);
        session(['supabase_user' => $newUser]);

        return redirect()->route('finance.initial');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        session()->forget('supabase_user');
        return redirect()->route('login');
    }
}
