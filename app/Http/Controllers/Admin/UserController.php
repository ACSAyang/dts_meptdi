<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\TestEmail;
use App\Models\User;
use App\Services\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

/** Accounts are never deleted (records stay linked to them); they are deactivated instead. */
class UserController extends Controller
{
    public function index()
    {
        return view('admin.users.index', [
            'users' => User::orderByDesc('is_active')->orderBy('last_name')->orderBy('first_name')->get(),
            'newUser' => new User(['is_active' => true]),
        ]);
    }

    public function create()
    {
        return view('admin.users.form', ['user' => new User(['is_active' => true])]);
    }

    public function store(Request $request)
    {
        $data = $request->validateWithBag('user', $this->rules() + [
            'password' => ['required', 'confirmed', 'min:8'],
        ]);
        $data['is_active'] = $request->boolean('is_active');

        $user = User::create($data);

        Audit::log('user.created', $user, 'Created account '.$user->username, null,
            collect($data)->except('password', 'password_confirmation')->all());

        // Send the account creation notice when an e-mail address was given.
        // A mail failure never cancels the account creation.
        if ($user->email) {
            try {
                Mail::to($user->email)->send(new TestEmail(username: $user->username, loginUrl: route('login')));
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return redirect()->route('admin.users.index')->with('success', 'Account created for '.$user->displayName().'.');
    }

    public function edit(User $user)
    {
        return view('admin.users.form', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate($this->rules($user) + ['password' => ['nullable', 'confirmed', 'min:8']]);
        $data['is_active'] = $request->boolean('is_active');

        if ($user->is($request->user()) && ! $data['is_active']) {
            return back()->withInput()->with('error', 'You cannot deactivate your own account.');
        }
        if (empty($data['password'])) {
            unset($data['password']);
        }

        $user->fill($data);
        $changes = collect($user->getDirty())->except('password')->all();
        $old = array_intersect_key($user->getOriginal(), $changes);
        $passwordChanged = $user->isDirty('password');
        $user->save();

        Audit::log('user.updated', $user, 'Updated account '.$user->username.($passwordChanged ? ' (password reset)' : ''), $old, $changes);

        return redirect()->route('admin.users.index')->with('success', 'Account updated.');
    }

    private function rules(?User $user = null): array
    {
        return [
            'rank' => ['required', Rule::in(array_keys(User::ranks()))],
            'first_name' => ['required', 'string', 'max:100'],
            'middle_initial' => ['nullable', 'string', 'max:2', 'regex:/^[A-Za-z]\.?$/'],
            'last_name' => ['required', 'string', 'max:100'],
            'serial_number' => ['required', 'string', 'max:50'],
            'designation' => ['nullable', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('users')->ignore($user)],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users')->ignore($user)],
        ];
    }
}
