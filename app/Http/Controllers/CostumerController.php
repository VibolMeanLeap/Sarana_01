<?php

namespace App\Http\Controllers;

use App\Models\Costumer;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CostumerController extends Controller
{
    public function index()
    {
        $customers = Costumer::with('user')->orderByDesc('customer_id')->paginate(10);
        $totalCustomers = Costumer::count();

        return view('admin.costumer', compact('customers', 'totalCustomers'));
    }

    public function webLogin()
    {
        if (Auth::check() && strtolower((string) Auth::user()->role?->role_name) === 'customer') {
            return redirect()->route('web.shop');
        }

        return view('website.login.login');
    }

    public function webRegister()
    {
        if (Auth::check() && strtolower((string) Auth::user()->role?->role_name) === 'customer') {
            return redirect()->route('web.shop');
        }

        return view('website.register.register');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:users,phone',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $customerRole = Role::query()
            ->whereRaw('LOWER(role_name) = ?', ['customer'])
            ->first();

        if (!$customerRole) {
            return back()->withInput()->withErrors([
                'register_error' => 'The customer role is missing. Please import the updated demo database.',
            ]);
        }

        $user = DB::transaction(function () use ($validated, $customerRole) {
            $newUser = User::create([
                'name' => $validated['name'],
                'phone' => $validated['phone'],
                'email' => $validated['email'],
                'username' => $validated['phone'],
                'password' => Hash::make($validated['password']),
                'role_id' => $customerRole->role_id,
                'status' => 'active',
            ]);

            Costumer::create([
                'user_id' => $newUser->user_id,
                'name' => $validated['name'],
                'phone' => $validated['phone'],
            ]);

            return $newUser;
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('web.shop')->with('success', 'Your customer account is ready. Welcome to Coffee Corner!');
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'phone' => 'required|string|max:20',
            'password' => 'required|string|min:8',
        ]);

        $user = User::with('role')
            ->where('phone', $validated['phone'])
            ->where('status', 'active')
            ->whereHas('role', function ($query) {
                $query->whereRaw('LOWER(role_name) = ?', ['customer']);
            })
            ->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return back()->withErrors([
                'phone' => 'The phone number or password is incorrect.',
            ])->onlyInput('phone');
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->route('web.shop')->with('success', 'You are signed in. Welcome back!');
    }

    public function account()
    {
        $customer = Costumer::with([
            'orders.orderItems.product',
            'orders.orderType',
            'orders.paymentMethod',
        ])->where('user_id', Auth::id())->first();

        return view('website.account', compact('customer'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('webLogin');
    }
}
