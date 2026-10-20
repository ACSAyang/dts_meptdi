<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Audit;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Mail;
use App\Mail\TestEmail;

/** Accounts are never deleted (records stay linked to them); they are deactivated instead. */
class UserController extends Controller
{
    public function index()
    {
        return view('admin.users.index', ['users' => User::orderByDesc('is_active')->orderBy('name')->get()]);
    }

    public function create()
    {
        return view('admin.users.form', ['user' => new User(['is_active' => true])]);
    }



    public function store(Request $request)
    {
        $data = $request->validate(
            $this->rules() + [
                'password' => ['required', 'confirmed', 'min:8'],
            ]
        );

        $data['is_active'] = $request->boolean('is_active');

        // Create user account
        $user = User::create($data);

        // Record audit log
        Audit::log(
            'user.created',
            $user,
            'Created account ' . $user->username,
            null,
            collect($data)
                ->except('password', 'password_confirmation')
                ->all()
        );

        // Send formal account creation email
        try {
            Mail::to($user->email)->send(
                new TestEmail(
                    username: $user->username,
                    loginUrl: route('login')
                )
            );
        } catch (\Throwable $e) {
            // Log email errors without cancelling account creation
            report($e);
        }

        // Redirect after account creation
        return redirect()
            ->route('admin.users.index')
            ->with(
                'success',
                'Account created for ' . $user->displayName() . '.'
            );
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

        Audit::log('user.updated', $user, 'Updated account ' . $user->username . ($passwordChanged ? ' (password reset)' : ''), $old, $changes);

        return redirect()->route('admin.users.index')->with('success', 'Account updated.');
    }

    private function rules(?User $user = null): array
    {
        return [
            'rank' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'serial_number' => ['required', 'string', 'max:50'],
            'designation' => ['nullable', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('users')->ignore($user)],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users')->ignore($user)],
        ];
    }
}
