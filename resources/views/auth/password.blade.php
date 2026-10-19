<x-layouts.app title="Change Password">
    <div class="card max-w-lg">
        <div class="card-header"><h2 class="card-title">Change password</h2></div>
        <form method="POST" action="{{ route('password.update') }}" class="space-y-4 p-5">
            @csrf @method('PUT')
            <x-field label="Current password" name="current_password" type="password" required />
            <x-field label="New password" name="password" type="password" required hint="At least 8 characters." />
            <x-field label="Confirm new password" name="password_confirmation" type="password" required />
            <div class="flex justify-end"><button class="btn btn-primary">Update password</button></div>
        </form>
    </div>
</x-layouts.app>
