<div class="space-y-6">
    @if ($isInvalid)
        <div class="rounded-card border border-rose-200 bg-rose-50 p-6 text-center text-rose-900">
            <h2 class="text-lg font-semibold text-rose-800">Invalid or Expired Invitation</h2>
            <p class="mt-2 text-sm text-rose-700">
                This activation link is either invalid or has already been used to activate an account.
            </p>
            <div class="mt-6">
                <a href="{{ route('login') }}" class="btn-primary inline-block">
                    Return to Log In
                </a>
            </div>
        </div>
    @else
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-slate-900">Activate Your Account</h2>
            <p class="mt-2 text-sm text-slate-600">
                Welcome, <strong>{{ $customer->name }}</strong>. Please create a strong password to access your Brightloop customer portal.
            </p>
        </div>

        <form wire:submit="activate" class="space-y-4">
            <div>
                <label for="email" class="block text-sm font-medium text-slate-700">Email address</label>
                <input id="email" type="text" value="{{ $customer->email }}" disabled class="field-input mt-1 bg-slate-100 text-slate-500 cursor-not-allowed w-full" />
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-slate-700">New Password</label>
                <input id="password" type="password" wire:model="password" required class="field-input mt-1 w-full" placeholder="At least 9 characters, mixed case, and symbols" />
                @error('password')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-slate-700">Confirm Password</label>
                <input id="password_confirmation" type="password" wire:model="password_confirmation" required class="field-input mt-1 w-full" placeholder="Confirm your new password" />
            </div>

            <div class="pt-2">
                <button type="submit" class="btn-primary w-full justify-center py-2.5">
                    Activate Account &amp; Proceed
                </button>
            </div>
        </form>
    @endif
</div>

