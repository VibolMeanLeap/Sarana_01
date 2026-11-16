<?php

namespace App\Http\Controllers;

use App\Models\Costumer;
use App\Models\Order;
use App\Models\Role;
use App\Models\User;
use App\Support\CustomerPhoneNumber;
use App\Support\AdminDateFilter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class CostumerController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $customers = Costumer::with('user')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($nested) use ($search) {
                    $nested->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($userQuery) => $userQuery->where('email', 'like', "%{$search}%"));
                });
            })
            ->orderBy(
                ['customer_id' => 'customer_id', 'name' => 'name'][$request->input('sort_by')] ?? 'customer_id',
                strtolower((string) $request->input('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc'
            )
            ->paginate(10)
            ->withQueryString();
        $totalCustomers = Costumer::count();

        return view('admin.costumer', compact('customers', 'totalCustomers'));
    }

    /** Customer login-account directory. Staff accounts are managed from Settings. */
    public function accounts(Request $request)
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'in:active,inactive'],
            'sort_by' => ['nullable', 'in:created_at,name,user_id'],
            'sort_dir' => ['nullable', 'in:asc,desc'],
        ]);

        $dateFilter = AdminDateFilter::resolve($request, 'start_date', 'end_date');
        $customerRole = Role::query()
            ->whereRaw('LOWER(role_name) = ?', ['customer'])
            ->first();

        $query = User::query()->with(['role', 'customer.orders']);
        if ($customerRole) {
            $query->where('role_id', $customerRole->role_id);
        } else {
            $query->whereRaw('1 = 0');
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%')
                    ->orWhere('phone', 'like', '%'.$search.'%')
                    ->orWhere('username', 'like', '%'.$search.'%');
            });
        }

        if ($request->filled('status') && in_array($request->input('status'), ['active', 'inactive'], true)) {
            $query->where('status', $request->input('status'));
        }
        AdminDateFilter::apply($query, 'created_at', $dateFilter);
        $sortColumns = ['created_at' => 'created_at', 'name' => 'name', 'user_id' => 'user_id'];
        $sortBy = $sortColumns[$request->input('sort_by')] ?? 'created_at';
        $sortDirection = strtolower((string) $request->input('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        $customerAccounts = $query->orderBy($sortBy, $sortDirection)->orderByDesc('user_id')->paginate(15)->withQueryString();
        $roleId = $customerRole?->role_id;
        $totalCustomerAccounts = $roleId ? User::where('role_id', $roleId)->count() : 0;
        $activeCustomerAccounts = $roleId ? User::where('role_id', $roleId)->where('status', 'active')->count() : 0;
        $inactiveCustomerAccounts = max(0, $totalCustomerAccounts - $activeCustomerAccounts);

        return view('admin.user', compact(
            'customerAccounts', 'totalCustomerAccounts', 'activeCustomerAccounts', 'inactiveCustomerAccounts', 'dateFilter'
        ));
    }

    /** Edit the profile of a website customer. Staff roles are never editable here. */
    public function editCustomerAccount(string $user_id)
    {
        $customerRole = Role::query()
            ->whereRaw('LOWER(role_name) = ?', ['customer'])
            ->firstOrFail();

        $customerAccount = User::query()
            ->with('customer')
            ->where('user_id', $user_id)
            ->where('role_id', $customerRole->role_id)
            ->firstOrFail();

        return view('admin.customer_accounts.edit', compact('customerAccount'));
    }

    /** Update customer details without allowing a role change or deleting order history. */
    public function updateCustomerAccount(Request $request, string $user_id)
    {
        $customerRole = Role::query()
            ->whereRaw('LOWER(role_name) = ?', ['customer'])
            ->firstOrFail();

        $customerAccount = User::query()
            ->with('customer')
            ->where('user_id', $user_id)
            ->where('role_id', $customerRole->role_id)
            ->firstOrFail();

        $request->merge([
            'name' => trim((string) $request->input('name', '')),
            'phone' => trim((string) $request->input('phone', '')),
            'email' => strtolower(trim((string) $request->input('email', ''))),
        ]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'phone' => [
                'required', 'string', 'max:20',
                function (string $attribute, mixed $value, \Closure $fail) use ($customerAccount): void {
                    if (CustomerPhoneNumber::normalize((string) $value) === '') {
                        $fail('Enter a valid phone number. For a number outside Cambodia, include the + country prefix.');
                        return;
                    }

                    if (CustomerPhoneNumber::isUsedByUser((string) $value, (int) $customerAccount->user_id)) {
                        $fail('This phone number is already linked to another account, including an inactive account.');
                        return;
                    }

                    if (CustomerPhoneNumber::isUsedByPendingRegistration((string) $value)) {
                        $fail('This phone number is currently reserved by a pending Telegram registration.');
                    }
                },
            ],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($customerAccount->user_id, 'user_id')],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        DB::transaction(function () use ($customerAccount, $validated) {
            $oldPhone = $customerAccount->phone;
            $customerAccount->name = $validated['name'];
            $customerAccount->phone = $validated['phone'];
            $customerAccount->email = $validated['email'];
            $customerAccount->status = $validated['status'];

            // Public registration uses the phone number as the username. Keep those
            // values synchronized only when the customer has not chosen another username.
            if ((string) $customerAccount->username === (string) $oldPhone) {
                $customerAccount->username = $validated['phone'];
            }
            $customerAccount->save();

            $customerProfile = Costumer::query()->where('user_id', $customerAccount->user_id)->first();
            if (! $customerProfile) {
                $customerProfile = new Costumer();
                $customerProfile->user_id = $customerAccount->user_id;
            }
            $customerProfile->name = $validated['name'];
            $customerProfile->phone = $validated['phone'];
            $customerProfile->save();
        });

        return redirect()->route('auth.list')->with('success', 'Customer profile updated successfully.');
    }

    /** Only customer-role accounts can be activated or deactivated here. */
    public function updateAccountStatus(Request $request, string $user_id)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:active,inactive'],
        ]);

        $customerRole = Role::query()
            ->whereRaw('LOWER(role_name) = ?', ['customer'])
            ->firstOrFail();

        $customerAccount = User::query()
            ->where('user_id', $user_id)
            ->where('role_id', $customerRole->role_id)
            ->firstOrFail();

        $customerAccount->status = $validated['status'];
        $customerAccount->save();

        return back()->with('success', 'Customer account status updated successfully.');
    }

    /**
     * Archive a customer login instead of hard-deleting it. The phone and
     * Telegram identity are released for future registration, while the
     * customer profile and order history remain available for audit/reporting.
     */
    public function archiveCustomerAccount(string $user_id)
    {
        $customerRole = Role::query()
            ->whereRaw('LOWER(role_name) = ?', ['customer'])
            ->firstOrFail();

        DB::transaction(function () use ($user_id, $customerRole) {
            $customerAccount = User::query()
                ->where('user_id', $user_id)
                ->where('role_id', $customerRole->role_id)
                ->lockForUpdate()
                ->firstOrFail();

            $oldPhone = (string) ($customerAccount->phone ?? '');
            if ($oldPhone !== '' && (string) $customerAccount->username === $oldPhone) {
                $customerAccount->username = 'archived-customer-'.$customerAccount->user_id.'-'.Str::lower(Str::random(6));
            }

            $customerAccount->phone = null;
            $customerAccount->status = 'inactive';
            $customerAccount->telegram_chat_id = null;
            $customerAccount->telegram_verified_at = null;
            $customerAccount->save();

            // Intentionally retain the linked customer row and its phone as a
            // historical contact so prior orders/reviews remain connected.
        });

        return redirect()->route('auth.list')->with(
            'success',
            'Customer account archived. Sign-in is disabled and its phone/Telegram identity are released; customer and order history are preserved. The email address remains reserved.'
        );
    }

    public function webLogin()
    {
        if (Auth::check() && strtolower((string) Auth::user()->role?->role_name) === 'customer') {
            return redirect()->intended(route('web.shop'));
        }

        return view('website.login.login');
    }

    public function webRegister()
    {
        if (Auth::check() && strtolower((string) Auth::user()->role?->role_name) === 'customer') {
            return redirect()->intended(route('web.shop'));
        }

        if (session()->has('customer_telegram_registration_id')) {
            return redirect()->route('web.register.telegram.verify');
        }

        return view('website.register.register');
    }

    /** Backward-compatible entry point; registration is always Telegram-verified. */
    public function register(Request $request)
    {
        return app(CustomerTelegramRegistrationController::class)->store(
            $request,
            app(\App\Services\CustomerTelegramOtpService::class)
        );
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

        return redirect()->intended(route('web.shop'))->with('success', 'You are signed in. Welcome back!');
    }

    public function account()
    {
        $customer = Costumer::query()->where('user_id', Auth::id())->first();
        $orders = $customer
            ? $customer->orders()->with(['orderType', 'paymentMethod', 'delivery', 'payments', 'review'])
                ->orderByDesc('order_date')->paginate(10)->withQueryString()
            : Order::query()->whereRaw('1 = 0')->paginate(10);

        return view('website.account', compact('customer', 'orders'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('webLogin');
    }
}
