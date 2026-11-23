<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        {{-- Display Picture (DP) Upload Section --}}
        <div x-data="{
                photoPreview: null,
                updatePreview() {
                    const reader = new FileReader();
                    reader.onload = (e) => { this.photoPreview = e.target.result; };
                    reader.readAsDataURL($refs.photo.files[0]);
                }
             }"
             class="p-4 bg-gray-50 rounded-xl border border-gray-200 flex flex-wrap items-center gap-4">
            <div class="relative w-16 h-16 rounded-full overflow-hidden bg-gray-200 border-2 border-cs-blue shadow-inner flex items-center justify-center shrink-0">
                <template x-if="photoPreview">
                    <img :src="photoPreview" alt="New DP Preview" class="w-full h-full object-cover">
                </template>
                <template x-if="!photoPreview">
                    <div>
                        @if($user->avatar_url)
                            <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" class="w-full h-full object-cover" onerror="this.style.display='none'; if(this.nextElementSibling) this.nextElementSibling.style.display='flex';">
                            <div class="w-full h-full items-center justify-center bg-gradient-to-tr from-slate-700 to-slate-900 text-white font-bold text-base hidden">
                                {{ $user->initials }}
                            </div>
                        @else
                            <div class="w-full h-full flex items-center justify-center bg-gradient-to-tr from-slate-700 to-slate-900 text-white font-bold text-base">
                                {{ $user->initials }}
                            </div>
                        @endif
                    </div>
                </template>
            </div>

            <div class="flex-1 min-w-[220px]">
                <x-input-label for="avatar" :value="__('Display Picture (DP)')" class="font-bold uppercase tracking-wider text-xs" />
                <div class="mt-1 flex items-center gap-2">
                    <input type="file" id="avatar" name="avatar" x-ref="photo" @change="updatePreview()" accept="image/*"
                           class="text-xs text-gray-600 file:mr-2 file:py-1 file:px-3 file:rounded file:border-0 file:text-xs file:font-semibold file:bg-cs-blue file:text-white hover:file:bg-blue-800 cursor-pointer">
                    @if($user->avatar)
                        <label class="inline-flex items-center gap-1 text-xs text-red-600 hover:text-red-700 cursor-pointer font-semibold ml-2">
                            <input type="checkbox" name="remove_avatar" value="1" class="rounded text-red-600 focus:ring-0 text-xs">
                            <span>Remove</span>
                        </label>
                    @endif
                </div>
                <p class="text-[10px] text-gray-500 mt-1">Upload JPEG, PNG or WebP image up to 5MB.</p>
                <x-input-error class="mt-1" :messages="$errors->get('avatar')" />
            </div>
        </div>

        {{-- Role Display (Read-Only) --}}
        <div>
            <x-input-label :value="__('Company Role')" class="font-bold uppercase tracking-wider text-xs" />
            <div class="mt-1 flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-md text-xs font-bold
                    {{ $user->isAdmin() ? 'bg-purple-100 text-purple-800 border border-purple-200' : ($user->isHrManager() ? 'bg-indigo-100 text-indigo-800 border border-indigo-200' : ($user->isManager() ? 'bg-blue-100 text-blue-800 border border-blue-200' : 'bg-gray-100 text-gray-700 border border-gray-200')) }}">
                    <span>🔒</span> {{ $user->role ?: 'Employee' }}
                </span>
                <span class="text-[11px] text-gray-500">Role permissions are configured by company administrators.</span>
            </div>
        </div>

        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email Address')" />
            @if($user->isEmployee())
                <div class="relative mt-1">
                    <x-text-input id="email" name="email" type="email" class="block w-full bg-gray-100 text-gray-500 cursor-not-allowed border-gray-300" :value="$user->email" readonly />
                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs">🔒 Locked</span>
                </div>
                <p class="text-[11px] text-gray-500 mt-1">
                    🔒 Company Policy: Employee email addresses cannot be modified self-service. Contact your HR manager or Super Admin for changes.
                </p>
            @else
                <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
                <x-input-error class="mt-2" :messages="$errors->get('email')" />
            @endif

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-gray-800">
                        {{ __('Your email address is unverified.') }}

                        <button form="send-verification" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-green-600">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save Changes') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
