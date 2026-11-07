<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Settings;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class SettingController extends Controller
{
    /**
     * There is one store profile for the whole application, not a list of
     * independently active settings records.
     */
    public function index()
    {
        $shop = Settings::query()->orderByDesc('id')->first();

        // Only staff roles are available here. Customer accounts remain in the
        // public customer registration workflow and cannot be created as staff.
        $staffRoles = Role::query()
            ->whereIn(DB::raw('LOWER(role_name)'), ['admin', 'cashier'])
            ->orderBy('role_name')
            ->get();

        $staffRoleIds = $staffRoles->pluck('role_id');
        $staffAccounts = User::query()
            ->with('role')
            ->whereIn('role_id', $staffRoleIds)
            ->orderByDesc('user_id')
            ->limit(10)
            ->get();

        $staffTotal = User::query()->whereIn('role_id', $staffRoleIds)->count();
        $adminRoleIds = $staffRoles->filter(fn ($role) => strtolower((string) $role->role_name) === 'admin')->pluck('role_id');
        $cashierRoleIds = $staffRoles->filter(fn ($role) => strtolower((string) $role->role_name) === 'cashier')->pluck('role_id');
        $activeAdmins = User::query()->whereIn('role_id', $adminRoleIds)->where('status', 'active')->count();
        $activeCashiers = User::query()->whereIn('role_id', $cashierRoleIds)->where('status', 'active')->count();

        return view('admin.meanleap.setting', compact(
            'shop', 'staffRoles', 'staffAccounts', 'staffTotal', 'activeAdmins', 'activeCashiers'
        ));
    }

    /** Create an administrator or cashier account from the Settings workspace. */
    public function storeStaffAccount(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'username' => ['required', 'string', 'min:3', 'max:200', 'unique:users,username'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:20', 'unique:users,phone'],
            'role_id' => ['required', 'integer', 'exists:role,role_id'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'status' => ['required', 'in:active,inactive'],
        ], [
            'password.confirmed' => 'The password confirmation does not match.',
            'role_id.required' => 'Please choose whether this account is an administrator or cashier.',
        ]);

        $role = Role::query()->findOrFail($validated['role_id']);
        $roleName = strtolower(trim((string) $role->role_name));

        if (! in_array($roleName, ['admin', 'cashier'], true)) {
            throw ValidationException::withMessages([
                'role_id' => 'Only Administrator and Cashier accounts can be created here.',
            ]);
        }

        DB::transaction(function () use ($validated, $role) {
            User::create([
                'name' => trim($validated['name']),
                'username' => trim($validated['username']),
                'email' => strtolower(trim($validated['email'])),
                'phone' => trim($validated['phone']),
                'role_id' => $role->role_id,
                'password' => Hash::make($validated['password']),
                'status' => $validated['status'],
            ]);
        });

        return redirect()->route('setting.list')
            ->with('success', ucfirst($roleName).' account created successfully. The staff member can now sign in with their username and password.');
    }

    public function create()
    {
        $shop = Settings::query()->orderByDesc('id')->first();

        if ($shop) {
            return redirect()->route('setting.edit', $shop->id);
        }

        return view('admin.meanleap.Add');
    }

    public function store(Request $request)
    {
        $validated = $this->validatedSettings($request);
        $shop = Settings::query()->orderByDesc('id')->first() ?? new Settings();

        $this->saveSettings($shop, $validated, $request);

        return redirect()->route('setting.list')
            ->with('success', 'Store profile created successfully. Your new branding is now active throughout the application.');
    }

    public function edit($id)
    {
        $shop = Settings::findOrFail($id);

        return view('admin.meanleap.Edit', compact('shop'));
    }

    public function update(Request $request, $id)
    {
        $shop = Settings::findOrFail($id);
        $validated = $this->validatedSettings($request);

        $this->saveSettings($shop, $validated, $request);

        return redirect()->route('setting.list')
            ->with('success', 'Store profile updated. The new information will appear across the admin, cashier, POS and public website on the next page load.');
    }

    /**
     * A store profile must not be deleted: the whole interface depends on it.
     * Keep the legacy route safe in case an old browser tab still calls it.
     */
    public function delete($id)
    {
        return redirect()->route('setting.list')
            ->with('error', 'The store profile cannot be deleted. Edit the profile to change the information displayed across the system.');
    }

    private function validatedSettings(Request $request): array
    {
        return $request->validate([
            'shop_name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:2000'],
            'tel' => ['nullable', 'string', 'max:50'],
            'exchange_rate' => ['required', 'numeric', 'gt:0', 'max:1000000'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
        ], [
            'shop_name.required' => 'Please enter the shop name.',
            'exchange_rate.required' => 'Please enter the USD to KHR exchange rate.',
            'exchange_rate.gt' => 'The exchange rate must be greater than zero.',
            'logo.image' => 'The shop logo must be a valid image.',
            'logo.max' => 'The shop logo must be 3 MB or smaller.',
        ]);
    }

    private function saveSettings(Settings $shop, array $validated, Request $request): void
    {
        $oldLogo = $shop->logo;

        $shop->shop_name = trim($validated['shop_name']);
        $shop->address = $validated['address'] ?? null;
        $shop->tel = $validated['tel'] ?? null;
        $shop->exchange_rate = $validated['exchange_rate'];

        if ($request->hasFile('logo')) {
            $uploadDirectory = public_path('uploads');

            if (! is_dir($uploadDirectory)) {
                mkdir($uploadDirectory, 0755, true);
            }

            $extension = strtolower($request->file('logo')->getClientOriginalExtension());
            $filename = Str::uuid()->toString().'.'.$extension;
            $request->file('logo')->move($uploadDirectory, $filename);
            $shop->logo = $filename;
        }

        $shop->save();

        // Remove an obsolete uploaded logo only after the new record is saved.
        if ($request->hasFile('logo') && $oldLogo && basename($oldLogo) === $oldLogo) {
            $oldLogoPath = public_path('uploads/'.$oldLogo);
            if (is_file($oldLogoPath)) {
                @unlink($oldLogoPath);
            }
        }
    }
}
