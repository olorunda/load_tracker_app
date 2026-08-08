<x-guest-layout>
    <div class="w-full space-y-6" style="width: 100%;">
        <div class="space-y-1">
            <h2 class="font-display text-display text-on-surface">Welcome Back</h2>
            <p class="font-body-md text-body-md text-on-surface-variant">Sign in to your FleetManager control dashboard.</p>
        </div>

        <!-- Session Status -->
        <x-auth-session-status class="p-3 bg-primary/10 border border-primary/20 text-primary rounded-lg text-body-sm" :status="session('status')" />

        <form method="POST" action="{{ route('login') }}" class="w-full space-y-5" style="width: 100%;">
            @csrf

            <!-- Email Address -->
            <div class="w-full" style="width: 100%;">
                <label for="email" class="block text-label-md font-label-md text-on-surface mb-1.5 uppercase">Email Address</label>
                <div class="relative w-full" style="width: 100%;">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant text-[20px]">mail</span>
                    <input id="email" 
                           type="email" 
                           name="email" 
                           value="{{ old('email', 'test@example.com') }}" 
                           required 
                           autofocus 
                           autocomplete="username"
                           placeholder="name@company.com" 
                           style="width: 100%; display: block;"
                           class="w-full pl-10 pr-4 py-2.5 rounded-lg border border-outline-variant bg-surface text-body-md text-on-surface focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition-all"/>
                </div>
                <x-input-error :messages="$errors->get('email')" class="mt-1 text-error text-body-sm font-semibold" />
            </div>

            <!-- Password -->
            <div class="w-full" style="width: 100%;">
                <div class="flex justify-between items-center mb-1.5">
                    <label for="password" class="block text-label-md font-label-md text-on-surface uppercase">Password</label>
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="text-body-sm font-label-md text-primary hover:underline">
                            Forgot password?
                        </a>
                    @endif
                </div>
                <div class="relative w-full" style="width: 100%;">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant text-[20px]">lock</span>
                    <input id="password" 
                           type="password" 
                           name="password" 
                           value="password"
                           required 
                           autocomplete="current-password"
                           placeholder="••••••••" 
                           style="width: 100%; display: block;"
                           class="w-full pl-10 pr-4 py-2.5 rounded-lg border border-outline-variant bg-surface text-body-md text-on-surface focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition-all"/>
                </div>
                <x-input-error :messages="$errors->get('password')" class="mt-1 text-error text-body-sm font-semibold" />
            </div>

            <!-- Remember Me -->
            <div class="flex items-center justify-between pt-1">
                <label for="remember_me" class="inline-flex items-center gap-2 cursor-pointer">
                    <input id="remember_me" 
                           type="checkbox" 
                           name="remember"
                           class="w-4 h-4 rounded border-outline-variant text-primary focus:ring-primary/20 cursor-pointer">
                    <span class="text-body-sm text-on-surface-variant">Keep me logged in</span>
                </label>
            </div>

            <button type="submit" style="width: 100%; display: flex;" class="w-full py-3 px-4 bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:bg-primary/90 transition-colors shadow-xs items-center justify-center gap-2 text-base font-bold">
                Log In to Control Center <span class="material-symbols-outlined text-[20px]">arrow_forward</span>
            </button>
        </form>

        <div class="border-t border-outline-variant/60 pt-5 text-center text-body-sm text-on-surface-variant">
            Don't have an account? 
            <a href="{{ route('register') }}" class="font-bold text-primary hover:underline">Register New Fleet</a>
        </div>
    </div>
</x-guest-layout>
