<?php

namespace App\Http\Controllers;
use Exception;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Role;
use App\Models\Costumer;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB; 
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
// use Exception; // 💡 បន្ថែម Exception class សម្រាប់ដោះស្រាយ catch block

class AuthContoller extends Controller
{
    // 💡 បន្ថែម function នេះដើម្បីបង្ហាញទំព័រ Login
    public function showLogin()
    {
        if (Auth::check()) {
            return strtolower((string) Auth::user()->role?->role_name) === 'admin'
                ? redirect()->route('admin.dashboard')
                : (strtolower((string) Auth::user()->role?->role_name) === 'cashier'
                    ? redirect()->route('cashier.dashboard')
                    : redirect()->route('web.shop'));
        }

        return view('admin.login');
    }
    /**
     * Keep the legacy dashboard action compatible with the new date-filtered dashboard.
     * Some older route files still point /admin to AuthContoller@dash.
     */
    public function dash(Request $request)
    {
        return app(DashboardController::class)->index($request);
    }

    public function index (Request $request){
        if($request->get('search')!=''){
             $user_key = User::with('role')->orderBy('user_id', 'desc')->where('name','like','%'.$request->get('search').'%')->paginate(10);
        }else{
             $user_key = User::with('role')->orderBy('user_id', 'desc')->paginate(10);
        }
        $totalCustomers = Costumer::count();
        $totalUser = User::count();
        return view("admin.user",compact('user_key','totalCustomers','totalUser'));
    }

    public function create(){
        $role = Role::select('role_id', 'role_name')->get(); 
        
        return view('admin.crud_users.create', compact('role'));
    }

    public function store(Request $request){
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|min:4',
            'role_id' => 'required|exists:role,role_id',
            'email'    => 'required|email|unique:users,email', 
            'phone'    => 'required|string|max:20',
            'username' => 'required|string|unique:users,username', 
            'password' => 'required|string|min:6',
            'status' => 'nullable|in:active,inactive'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 422,
                'message' => "Validation failed",
                'errors'  => $validator->errors(),
            ], 422); 
        }

        try {
            $user = new User();
            $user->name     = $request->name;
            $user->role_id = $request->role_id;
            $user->email    = $request->email;
            $user->phone    = $request->phone;
            $user->username = $request->username;
            $user->password = Hash::make($request->password);
            $user->status = $request->input('status') === 'active' ? 'active' : 'inactive'; 
            $user->save(); 
            session()->flash('user_status', 'User Created Successfully');

            return response()->json([
                'status'  => 201, 
                'message' => "User Created Successfully"
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'status'  => 500,
                'message' => "Database error occurred",
                'error'   => $e->getMessage() 
            ], 500);
        }
    }

    public function edit(string $user_id){
        $user = User::findOrFail($user_id);
        $role = Role::all();
        return view('admin.crud_users.edit',compact('user','role'));
    }

    public function update(Request $request, string $user_id)
    {
        $user = User::find($user_id);

        if ($user == null) {
            return redirect()->back()->with('error', 'User not found');
        }

        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|min:4',
            'role_id'  => 'required|exists:role,role_id',
            'email'    => 'required|email|unique:users,email,' .  $user_id . ',user_id',
            'phone'    => 'required|string|max:20',
            'username' => 'required|string|unique:users,username,' .  $user_id . ',user_id',
            'password' => 'nullable|string|min:6', 
            'status'   => 'nullable|in:active,inactive'
        ]);

        if ($validator->passes()) {
            $user->name     = $request->name;
            $user->role_id  = $request->role_id; // 💡 កែពី $user->role_id ទៅ $request->role_id ដើម្បីឱ្យវា update ទិន្នន័យថ្មី
            $user->email    = $request->email;
            $user->phone    = $request->phone;
            $user->username = $request->username;

            if ($request->filled('password')) {
                $user->password = Hash::make($request->password);
            }

            $user->status = $request->input('status', 'inactive');
            $user->save(); 
            return redirect()->route('auth.list')->with('update_success', 'User Updated successfully');
        } else {
            return redirect()->back()->withInput()->withErrors($validator);
        }
    }

    public function delete(string $user_id)
    {
        $user = User::find($user_id);
        if (! $user) {
            return redirect()->back()->with('error', 'User not found.');
        }

        $hasHistory = DB::table('orders')->where('cashier_id', $user->user_id)->exists()
            || DB::table('purchases')->where('user_id', $user->user_id)->exists()
            || DB::table('stock_logs')->where('user_id', $user->user_id)->exists()
            || DB::table('cash_transactions')->where('user_id', $user->user_id)->exists()
            || DB::table('customer')->where('user_id', $user->user_id)->exists();

        if ($hasHistory) {
            $user->status = 'inactive';
            $user->save();

            return redirect()->back()->with(
                'delete_success',
                'This account has historical records, so it was deactivated instead of deleted.'
            );
        }

        $user->delete();

        return redirect()->back()->with('delete_success', 'User deleted successfully.');
    }


    // authentication
    public function showRegister (){
        // if(Auth::check){
        //     return redirect()->route('auth.list');
        // }
        return view('admin.register');
    }
    public function processRegister(Request $request){
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|min:4|unique:users,name', 
            'password' => 'required|string|min:4',
            'con_pass' => 'required|same:password',
        ]);

        if ($validator->passes()) {
            $adminRole = DB::table('role')->where('role_name', 'admin')->first();
            if (!$adminRole) {
                return redirect()->back()->withInput()->withErrors([
                    'register_error' => 'Not!'
                ]);
            }
            $user = new User();
            $user->name     = $request->name;
            $user->password = Hash::make($request->password);
            $user->status   = 'active';            
            $user->role_id  = $adminRole->role_id;  
            $user->save(); 
            return redirect()->route('showlogin')->with('success', 'Admin account registration successful.!');
        } else {
            return redirect()->back()->withInput()->withErrors($validator);
        }
    }
   
    public function processlogin(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|min:3|max:200',
            'password' => 'required|string|min:6',
        ]);

        $user = User::with('role')
            ->where('username', $validated['name'])
            ->where('status', 'active')
            ->whereHas('role', function ($query) {
                $query->whereIn(DB::raw('LOWER(role_name)'), ['admin', 'cashier']);
            })
            ->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return redirect()->back()->withInput($request->only('name'))
                ->with('error', 'Incorrect staff username or password. Please try again.');
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        if (strtolower((string) $user->role?->role_name) === 'admin') {
            return redirect()->route('admin.dashboard')->with('success_login', 'Welcome back, administrator.');
        }

        return redirect()->route('cashier.dashboard')->with('success_login', 'Welcome back. Your cashier workspace is ready.');
    }

    public function logout(Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('showlogin'); 
    }
}