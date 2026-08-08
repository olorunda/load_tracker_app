<x-guest-layout>
    <div class="w-full space-y-6">
        <div class="space-y-1">
            <h2 class="font-display text-display text-on-surface">Register Account</h2>
            <p class="font-body-md text-body-md text-on-surface-variant">Create your FleetManager organization credentials.</p>
        </div>

        <form method="POST" action="{{ route('register') }}" class="w-full space-y-4">
            @csrf

            <!-- Full Name -->
            <div class="w-full">
                <label for="name" class="block text-label-md font-label-md text-on-surface mb-1.5 uppercase">Full Name</label>
                <div class="relative w-full">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant text-[20px]">person</span>
                    <input id="name" 
                           type="text" 
                           name="name" 
                           value="{{ old('name') }}" 
                           required 
                           autofocus 
                           autocomplete="name"
                           placeholder="e.g. Alex Mercer" 
                           class="w-full pl-10 pr-4 py-2.5 rounded-lg border border-outline-variant bg-surface text-body-md text-on-surface focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition-all"/>
                </div>
                <x-input-error :messages="$errors->get('name')" class="mt-1 text-error text-body-sm font-semibold" />
            </div>

            <!-- Email Address -->
            <div class="w-full">
                <label for="email" class="block text-label-md font-label-md text-on-surface mb-1.5 uppercase">Work Email</label>
                <div class="relative w-full">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant text-[20px]">mail</span>
                    <input id="email" 
                           type="email" 
                           name="email" 
                           value="{{ old('email') }}" 
                           required 
                           autocomplete="username"
                           placeholder="name@company.com" 
                           class="w-full pl-10 pr-4 py-2.5 rounded-lg border border-outline-variant bg-surface text-body-md text-on-surface focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition-all"/>
                </div>
                <x-input-error :messages="$errors->get('email')" class="mt-1 text-error text-body-sm font-semibold" />
            </div>

            <!-- Password -->
            <div class="w-full">
                <label for="password" class="block text-label-md font-label-md text-on-surface mb-1.5 uppercase">Password</label>
                <div class="relative w-full">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant text-[20px]">lock</span>
                    <input id="password" 
                           type="password" 
                           name="password" 
                           required 
                           autocomplete="new-password"
                           placeholder="Create secure password" 
                           class="w-full pl-10 pr-4 py-2.5 rounded-lg border border-outline-variant bg-surface text-body-md text-on-surface focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition-all"/>
                </div>
                <x-input-error :messages="$errors->get('password')" class="mt-1 text-error text-body-sm font-semibold" />
            </div>

            <!-- Confirm Password -->
            <div class="w-full">
                <label for="password_confirmation" class="block text-label-md font-label-md text-on-surface mb-1.5 uppercase">Confirm Password</label>
                <div class="relative w-full">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant text-[20px]">lock_reset</span>
                    <input id="password_confirmation" 
                           type="password" 
                           name="password_confirmation" 
                           required 
                           autocomplete="new-password"
                           placeholder="Repeat password" 
                           class="w-full pl-10 pr-4 py-2.5 rounded-lg border border-outline-variant bg-surface text-body-md text-on-surface focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none transition-all"/>
                </div>
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1 text-error text-body-sm font-semibold" />
            </div>

            <button type="submit" class="w-full py-3 px-4 bg-primary text-on-primary rounded-lg font-label-md text-label-md hover:bg-primary/90 transition-colors shadow-xs flex items-center justify-center gap-2 text-base font-bold">
                Create Fleet Account <span class="material-symbols-outlined text-[20px]">how_to_reg</span>
            </button>
        </form>

        <div class="border-t border-outline-variant/60 pt-4 text-center text-body-sm text-on-surface-variant">
            Already registered? 
            <a href="{{ route('login') }}" class="font-bold text-primary hover:underline">Log In Here</a>
        </div>
    </div>
</x-guest-layout>
