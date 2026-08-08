<x-guest-layout>
    <div class="space-y-sm">
        <h2 class="font-display text-display text-on-surface">Password Recovery</h2>
        <p class="font-body-md text-body-md text-on-surface-variant">
            Enter your registered email address and we'll send you a password reset link.
        </p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-md p-sm bg-[#059669]/10 border border-[#059669]/20 text-[#059669] rounded-lg text-body-sm" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-md">
        @csrf

        <!-- Email Address -->
        <div>
            <label for="email" class="block text-label-md font-label-md text-on-surface mb-xs uppercase">Email Address</label>
            <div class="relative">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant text-[20px]">mail</span>
                <input id="email" 
                       type="email" 
                       name="email" 
                       value="{{ old('email') }}" 
                       required 
                       autofocus 
                       placeholder="name@company.com" 
                       class="w-full pl-10 pr-4 py-2.5 rounded-lg border border-outline-variant bg-surface text-body-md text-on-surface focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition-all"/>
            </div>
            <x-input-error :messages="$errors->get('email')" class="mt-xs text-error text-body-sm font-semibold" />
        </div>

        <button type="submit" class="w-full py-3 px-md bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:bg-primary/90 transition-colors shadow-xs flex items-center justify-center gap-2 text-base font-bold">
            Send Reset Link <span class="material-symbols-outlined text-[20px]">mark_email_read</span>
        </button>
    </form>

    <div class="border-t border-outline-variant/60 pt-md text-center text-body-sm text-on-surface-variant">
        Remember your credentials? 
        <a href="{{ route('login') }}" class="font-bold text-primary hover:underline">Back to Log In</a>
    </div>
</x-guest-layout>
