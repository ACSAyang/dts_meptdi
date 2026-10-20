{{--
    User account fields, shared by the edit page and the "Add user" pop-up.
    $user, $p (id prefix), $bag (error bag), $useOld (refill from last submit)
--}}
@php
    $p ??= '';
    $bag ??= 'default';
    $useOld ??= true;
    $isActive = $useOld ? old('is_active', $user->is_active) : $user->is_active;
    $rankOptions = collect(config('dts.ranks'))
        ->map(fn ($ranks) => collect($ranks)->mapWithKeys(fn ($title, $abbr) => [$abbr => "$abbr — $title"])->all())
        ->all();
@endphp
<div class="space-y-6">
    <fieldset>
        <legend class="card-title mb-3">Personal information</legend>
        <div class="grid gap-4 md:grid-cols-12">
            <x-field label="Rank" name="rank" :id="$p.'rank'" :options="$rankOptions" :value="$user->rank" required class="md:col-span-4" :bag="$bag" :use-old="$useOld" />
            <x-field label="Serial number" name="serial_number" :id="$p.'serial_number'" :value="$user->serial_number" required class="md:col-span-4" :bag="$bag" :use-old="$useOld" />
            <x-field label="Designation" name="designation" :id="$p.'designation'" :value="$user->designation" placeholder="e.g. Duty Office Watch" class="md:col-span-4" :bag="$bag" :use-old="$useOld" />
            <x-field label="First name" name="first_name" :id="$p.'first_name'" :value="$user->first_name" required class="md:col-span-5" :bag="$bag" :use-old="$useOld" />
            <x-field label="M.I." name="middle_initial" :id="$p.'middle_initial'" :value="$user->middle_initial" maxlength="2" placeholder="e.g. U" class="md:col-span-2" :bag="$bag" :use-old="$useOld" />
            <x-field label="Last name" name="last_name" :id="$p.'last_name'" :value="$user->last_name" required class="md:col-span-5" :bag="$bag" :use-old="$useOld" />
        </div>
        <p class="hint mt-2">Shown on records as <strong class="text-navy-800" data-name-preview="{{ $p }}">{{ $user->exists ? $user->displayName() : 'ASN Juan A Dela Cruz PCG' }}</strong></p>
    </fieldset>

    <fieldset>
        <legend class="card-title mb-3">Sign-in</legend>
        <div class="grid gap-4 md:grid-cols-2">
            <x-field label="Username" name="username" :id="$p.'username'" :value="$user->username" required autocomplete="off" :bag="$bag" :use-old="$useOld" />
            <x-field label="E-mail" name="email" :id="$p.'email'" type="email" :value="$user->email" hint="Optional. Used for the account notice and password reset." :bag="$bag" :use-old="$useOld" />
            <x-field :label="$user->exists ? 'New password (leave blank to keep)' : 'Password'" name="password" :id="$p.'password'" type="password" :required="! $user->exists" autocomplete="new-password" hint="At least 8 characters." :bag="$bag" />
            <x-field label="Confirm password" name="password_confirmation" :id="$p.'password_confirmation'" type="password" :required="! $user->exists" autocomplete="new-password" :bag="$bag" />
        </div>
        <label class="mt-4 flex items-center gap-2 text-sm text-slate-700">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" class="rounded border-navy-300" @checked($isActive)> Account active
        </label>
    </fieldset>
</div>
