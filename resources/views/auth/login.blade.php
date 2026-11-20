<x-guest-layout>
    <div class="mb-6">
        <h2 class="text-xl font-bold text-gray-800">Welcome back</h2>
        <p class="text-xs text-gray-500 mt-1">Please enter your credentials to access your time clock and workspace.</p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4 text-xs font-semibold text-emerald-700 bg-emerald-50 p-3 rounded-lg border border-emerald-200" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <!-- Email Address -->
        <div>
            <label for="email" class="block text-xs font-semibold text-gray-700 mb-1">Email Address</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                   placeholder="you@company.com"
                   class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-300 rounded-lg text-sm text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#005a9c] focus:border-transparent transition" />
            <x-input-error :messages="$errors->get('email')" class="mt-1.5 text-xs text-red-600" />
        </div>

        <!-- Password -->
        <div>
            <div class="flex items-center justify-between mb-1">
                <label for="password" class="block text-xs font-semibold text-gray-700">Password</label>
                @if (Route::has('password.request'))
                    <a class="text-xs text-[#005a9c] hover:underline font-medium" href="{{ route('password.request') }}">
                        Forgot password?
                    </a>
                @endif
            </div>
            <input id="password" type="password" name="password" required autocomplete="current-password"
                   placeholder="••••••••"
                   class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-300 rounded-lg text-sm text-gray-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#005a9c] focus:border-transparent transition" />
            <x-input-error :messages="$errors->get('password')" class="mt-1.5 text-xs text-red-600" />
        </div>

        <!-- Remember Me -->
        <div class="flex items-center justify-between pt-1">
            <label for="remember_me" class="inline-flex items-center cursor-pointer">
                <input id="remember_me" type="checkbox" name="remember"
                       class="w-4 h-4 text-[#005a9c] border-gray-300 rounded focus:ring-[#005a9c]">
                <span class="ms-2 text-xs text-gray-600">Keep me logged in</span>
            </label>
        </div>

        <div class="pt-2">
            <button type="submit"
                    class="w-full py-2.5 px-4 bg-[#005a9c] hover:bg-blue-800 text-white font-bold rounded-lg text-sm shadow-md hover:shadow-lg transition duration-150 ease-in-out flex items-center justify-center gap-2">
                <span>Sign In to ClockVerse</span>
                <span class="text-xs">&rarr;</span>
            </button>
        </div>

        {{-- Divider --}}
        <div class="relative my-4">
            <div class="absolute inset-0 flex items-center">
                <div class="w-full border-t border-gray-200"></div>
            </div>
            <div class="relative flex justify-center text-xs uppercase">
                <span class="bg-white px-2 text-gray-400 font-semibold">Or passkey</span>
            </div>
        </div>

        {{-- Passkey Quick Login --}}
        <div>
            <button type="button" id="btn-passkey-login" onclick="loginWithPasskey()"
                    class="w-full py-2.5 px-4 bg-slate-800 hover:bg-slate-900 text-white font-bold rounded-lg text-sm shadow hover:shadow-md transition duration-150 flex items-center justify-center gap-2">
                <i class="fa-solid fa-key text-amber-400"></i>
                <span>Quick Sign in with Passkey</span>
            </button>
            <div id="passkey-error" class="hidden mt-2 p-2.5 bg-rose-50 border border-rose-200 rounded-lg text-xs text-rose-700 font-medium text-center"></div>
        </div>
    </form>

    <script src="/vendor/webauthn/webauthn.js"></script>
    <script>
        async function loginWithPasskey() {
            const errorDiv = document.getElementById('passkey-error');
            errorDiv.classList.add('hidden');
            errorDiv.textContent = '';

            if (window.location.hostname === '127.0.0.1') {
                errorDiv.innerHTML = '<span>⚠️ Passkey requires a domain name (not raw IP 127.0.0.1). Please <a href="' + window.location.href.replace('127.0.0.1', 'localhost') + '" class="underline font-bold text-rose-900">click here to switch to http://localhost:8000</a> to sign in with passkey.</span>';
                errorDiv.classList.remove('hidden');
                return;
            }

            if (typeof WebAuthn === 'undefined' || !WebAuthn.supportsWebAuthn()) {
                errorDiv.textContent = 'WebAuthn passkeys are not supported by this browser or platform.';
                errorDiv.classList.remove('hidden');
                return;
            }

            const emailInput = document.getElementById('email');
            const email = emailInput ? emailInput.value.trim() : '';
            const btn = document.getElementById('btn-passkey-login');
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1"></i> Waiting for biometric / passkey...';

            try {
                const webauthn = new WebAuthn({
                    loginOptions: '/webauthn/login/options',
                    login: '/webauthn/login'
                });

                await webauthn.login(email ? { email: email } : {});
                window.location.href = '/dashboard';
            } catch (e) {
                console.error(e);
                btn.disabled = false;
                btn.innerHTML = originalText;
                errorDiv.textContent = (e && e.message) ? e.message : 'Passkey verification failed or cancelled.';
                errorDiv.classList.remove('hidden');
            }
        }
    </script>
</x-guest-layout>
