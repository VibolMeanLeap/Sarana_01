<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Settings;
use App\Models\User;
use App\Support\StoreDeliveryFee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SettingController extends Controller
{
    /** Store profile and staff access workspace. */
    public function index()
    {
        $shop = Settings::query()->orderByDesc('id')->first();
        $deliveryFee = StoreDeliveryFee::current();

        $staffRoles = Role::query()
            ->whereIn(DB::raw('LOWER(role_name)'), ['admin', 'cashier'])
            ->orderBy('role_name')
            ->get();

        $staffRoleIds = $staffRoles->pluck('role_id');
        $staffAccounts = User::query()
            ->with('role')
            ->whereIn('role_id', $staffRoleIds)
            ->orderByDesc('user_id')
            ->paginate(10)
            ->withQueryString();

        $staffTotal = User::query()->whereIn('role_id', $staffRoleIds)->count();
        $adminRoleIds = $staffRoles->filter(fn ($role) => strtolower((string) $role->role_name) === 'admin')->pluck('role_id');
        $cashierRoleIds = $staffRoles->filter(fn ($role) => strtolower((string) $role->role_name) === 'cashier')->pluck('role_id');
        $activeAdmins = User::query()->whereIn('role_id', $adminRoleIds)->where('status', 'active')->count();
        $activeCashiers = User::query()->whereIn('role_id', $cashierRoleIds)->where('status', 'active')->count();

        return view('admin.meanleap.setting', compact(
            'shop', 'staffRoles', 'staffAccounts', 'staffTotal', 'activeAdmins', 'activeCashiers', 'deliveryFee'
        ));
    }

    /** Create an administrator or cashier account from Settings. */
    public function storeStaffAccount(Request $request)
    {
        $this->normalizeStaffInput($request);
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'username' => ['required', 'string', 'min:3', 'max:200', 'unique:users,username'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:20', 'unique:users,phone'],
            'role_id' => ['required', 'integer', 'exists:role,role_id'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ], [
            'password.confirmed' => 'The password confirmation does not match.',
            'role_id.required' => 'Please choose Administrator or Cashier.',
        ]);

        $role = Role::query()->findOrFail($validated['role_id']);
        $roleName = strtolower(trim((string) $role->role_name));
        $this->ensureStaffRole($roleName);

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

        return redirect()->route('setting.list')->with('success', ucfirst($roleName).' account created successfully.');
    }

    /** Edit staff accounts only from the Settings workspace. */
    public function editStaffAccount(string $user_id)
    {
        $staff = $this->findStaffAccount($user_id);
        $staffRoles = Role::query()
            ->whereIn(DB::raw('LOWER(role_name)'), ['admin', 'cashier'])
            ->orderBy('role_name')
            ->get();

        return view('admin.meanleap.staff_edit', compact('staff', 'staffRoles'));
    }

    /** Update a staff profile, role, password or account status. */
    public function updateStaffAccount(Request $request, string $user_id)
    {
        $staff = $this->findStaffAccount($user_id);
        $this->normalizeStaffInput($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'username' => ['required', 'string', 'min:3', 'max:200', Rule::unique('users', 'username')->ignore($staff->user_id, 'user_id')],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($staff->user_id, 'user_id')],
            'phone' => ['required', 'string', 'max:20', Rule::unique('users', 'phone')->ignore($staff->user_id, 'user_id')],
            'role_id' => ['required', 'integer', 'exists:role,role_id'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $role = Role::query()->findOrFail($validated['role_id']);
        $newRoleName = strtolower(trim((string) $role->role_name));
        $this->ensureStaffRole($newRoleName);
        $this->protectLastAdministrator($staff, $newRoleName, $validated['status']);

        DB::transaction(function () use ($staff, $validated, $role) {
            $staff->name = trim($validated['name']);
            $staff->username = trim($validated['username']);
            $staff->email = strtolower(trim($validated['email']));
            $staff->phone = trim($validated['phone']);
            $staff->role_id = $role->role_id;
            $staff->status = $validated['status'];

            if (! empty($validated['password'])) {
                $staff->password = Hash::make($validated['password']);
            }

            $staff->save();
        });

        return redirect()->route('setting.list')->with('success', 'Staff account updated successfully.');
    }

    /** Preserve audit history by deactivating, never hard-deleting, staff accounts. */
    public function deactivateStaffAccount(string $user_id)
    {
        $staff = $this->findStaffAccount($user_id);
        $roleName = strtolower((string) $staff->role?->role_name);
        $this->protectLastAdministrator($staff, $roleName, 'inactive');

        $staff->status = 'inactive';
        $staff->save();

        return redirect()->route('setting.list')->with('success', 'Staff account deactivated. Historical sales and audit records were preserved.');
    }

    private function normalizeStaffInput(Request $request): void
    {
        $request->merge([
            'name' => trim((string) $request->input('name', '')),
            'username' => trim((string) $request->input('username', '')),
            'email' => strtolower(trim((string) $request->input('email', ''))),
            'phone' => trim((string) $request->input('phone', '')),
        ]);
    }

    private function findStaffAccount(string $userId): User
    {
        return User::query()
            ->with('role')
            ->where('user_id', $userId)
            ->whereHas('role', function ($query) {
                $query->whereIn(DB::raw('LOWER(role_name)'), ['admin', 'cashier']);
            })
            ->firstOrFail();
    }

    private function ensureStaffRole(string $roleName): void
    {
        if (! in_array($roleName, ['admin', 'cashier'], true)) {
            throw ValidationException::withMessages([
                'role_id' => 'Only Administrator and Cashier roles can be managed in Settings.',
            ]);
        }
    }

    private function protectLastAdministrator(User $staff, string $newRoleName, string $newStatus): void
    {
        $isCurrentUser = (int) auth()->id() === (int) $staff->user_id;
        if ($isCurrentUser && ($newRoleName !== 'admin' || $newStatus !== 'active')) {
            throw ValidationException::withMessages([
                'status' => 'You cannot deactivate your own administrator account or remove your administrator access.',
            ]);
        }

        $oldRoleName = strtolower((string) $staff->role?->role_name);
        $isLeavingActiveAdminRole = $oldRoleName === 'admin'
            && $staff->status === 'active'
            && ($newRoleName !== 'admin' || $newStatus !== 'active');

        if ($isLeavingActiveAdminRole) {
            $activeAdmins = User::query()
                ->where('status', 'active')
                ->whereHas('role', fn ($query) => $query->whereRaw('LOWER(role_name) = ?', ['admin']))
                ->count();

            if ($activeAdmins <= 1) {
                throw ValidationException::withMessages([
                    'status' => 'The last active administrator cannot be deactivated or changed to cashier. Create another administrator first.',
                ]);
            }
        }
    }

    public function create()
    {
        $shop = Settings::query()->orderByDesc('id')->first();
        if ($shop) {
            return redirect()->route('setting.edit', $shop->id);
        }
        $deliveryFee = StoreDeliveryFee::current();
        return view('admin.meanleap.Add', compact('deliveryFee'));
    }

    public function store(Request $request)
    {
        $validated = $this->validatedSettings($request);
        $shop = Settings::query()->orderByDesc('id')->first() ?? new Settings();
        $this->saveSettings($shop, $validated, $request);

        return redirect()->route('setting.list')->with('success', 'Store profile created successfully.');
    }

    public function edit($id)
    {
        $shop = Settings::findOrFail($id);
        $deliveryFee = StoreDeliveryFee::current();
        return view('admin.meanleap.Edit', compact('shop', 'deliveryFee'));
    }

    public function update(Request $request, $id)
    {
        $shop = Settings::findOrFail($id);
        $validated = $this->validatedSettings($request);
        $this->saveSettings($shop, $validated, $request);

        return redirect()->route('setting.list')->with('success', 'Store profile updated successfully.');
    }

    public function delete($id)
    {
        return redirect()->route('setting.list')->with('error', 'The store profile cannot be deleted. Edit the profile instead.');
    }

    private function validatedSettings(Request $request): array
    {
        return $request->validate([
            'shop_name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:2000'],
            'tel' => ['nullable', 'string', 'max:50'],
            'exchange_rate' => ['required', 'numeric', 'gt:0', 'max:1000000'],
            'delivery_fee' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
        ], [
            'shop_name.required' => 'Please enter the shop name.',
            'exchange_rate.required' => 'Please enter the USD to KHR exchange rate.',
            'delivery_fee.required' => 'Please enter the home delivery fee. Enter 0 for free delivery.',
            'delivery_fee.numeric' => 'The home delivery fee must be a valid number.',
            'delivery_fee.min' => 'The home delivery fee cannot be negative.',
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
        $shop->delivery_fee = round((float) $validated['delivery_fee'], 2);

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

        if ($request->hasFile('logo') && $oldLogo && basename($oldLogo) === $oldLogo) {
            $oldLogoPath = public_path('uploads/'.$oldLogo);
            if (is_file($oldLogoPath)) {
                @unlink($oldLogoPath);
            }
        }
    }
}
