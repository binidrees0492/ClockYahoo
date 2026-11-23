<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-bold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    {{ __('Personal Configurations & Profile') }}
                </h2>
                <p class="text-xs text-gray-500 mt-0.5">
                    Manage your profile information, app theme, personal email accounts, and biometric passkeys.
                </p>
            </div>
            <div class="hidden sm:flex items-center gap-2 text-xs text-gray-600 bg-white/70 px-3 py-1.5 rounded-lg border border-gray-200">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                <span>Active User: <strong>{{ Auth::user()->name }}</strong> ({{ Auth::user()->role ?: 'Employee' }})</span>
            </div>
        </div>
    </x-slot>

    <div class="py-8"
         x-data="{
            activeTab: 'profile',
            isDirty: false,
            targetTab: null,
            showUnsavedModal: false,
            markDirty() {
                this.isDirty = true;
            },
            clearDirty() {
                this.isDirty = false;
            },
            switchTab(tab) {
                if (this.activeTab === tab) return;
                if (this.isDirty) {
                    this.targetTab = tab;
                    this.showUnsavedModal = true;
                } else {
                    this.activeTab = tab;
                }
            },
            confirmSwitch() {
                this.isDirty = false;
                this.activeTab = this.targetTab;
                this.targetTab = null;
                this.showUnsavedModal = false;
            },
            cancelSwitch() {
                this.targetTab = null;
                this.showUnsavedModal = false;
            }
         }"
         @input="markDirty()"
         @change="markDirty()"
         @form-saved.window="clearDirty()">

        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- HORIZONTAL TABS BAR --}}
            <div class="bg-white rounded-xl shadow-xs border border-gray-200 p-2">
                <nav class="flex space-x-2 overflow-x-auto" aria-label="Tabs">
                    {{-- Tab 1: Profile Info --}}
                    <button type="button" @click.prevent="switchTab('profile')"
                            :class="activeTab === 'profile'
                                ? 'bg-[#005a9c] text-white shadow-xs font-bold'
                                : 'text-gray-600 hover:bg-gray-100 font-semibold'"
                            class="px-4 py-2.5 rounded-lg text-xs transition duration-150 flex items-center gap-2 whitespace-nowrap">
                        <i class="fa-solid fa-user"></i>
                        <span>Profile Information</span>
                    </button>

                    {{-- Tab 2: Theme & Appearance --}}
                    <button type="button" @click.prevent="switchTab('theme')"
                            :class="activeTab === 'theme'
                                ? 'bg-[#005a9c] text-white shadow-xs font-bold'
                                : 'text-gray-600 hover:bg-gray-100 font-semibold'"
                            class="px-4 py-2.5 rounded-lg text-xs transition duration-150 flex items-center gap-2 whitespace-nowrap">
                        <i class="fa-solid fa-palette"></i>
                        <span>Appearance & Theme</span>
                    </button>

                    {{-- Tab 3: Security & Passkeys --}}
                    <button type="button" @click.prevent="switchTab('security')"
                            :class="activeTab === 'security'
                                ? 'bg-[#005a9c] text-white shadow-xs font-bold'
                                : 'text-gray-600 hover:bg-gray-100 font-semibold'"
                            class="px-4 py-2.5 rounded-lg text-xs transition duration-150 flex items-center gap-2 whitespace-nowrap">
                        <i class="fa-solid fa-shield-halved"></i>
                        <span>Security & Passkeys</span>
                    </button>

                    {{-- Tab 4: Notification Preferences --}}
                    <button type="button" @click.prevent="switchTab('notifications')"
                            :class="activeTab === 'notifications'
                                ? 'bg-[#005a9c] text-white shadow-xs font-bold'
                                : 'text-gray-600 hover:bg-gray-100 font-semibold'"
                            class="px-4 py-2.5 rounded-lg text-xs transition duration-150 flex items-center gap-2 whitespace-nowrap">
                        <i class="fa-solid fa-bell"></i>
                        <span>Notification Preferences</span>
                    </button>
                </nav>
            </div>

            {{-- TAB 1: PROFILE INFORMATION --}}
            <div x-show="activeTab === 'profile'" x-cloak class="p-6 bg-white shadow-xs sm:rounded-xl border border-gray-200">
                <div class="max-w-2xl">
                    <div @submit="clearDirty()">
                        @include('profile.partials.update-profile-information-form')
                    </div>
                </div>
            </div>

            {{-- TAB 2: APPEARANCE & THEME --}}
            <div x-show="activeTab === 'theme'" x-cloak class="p-6 bg-white shadow-xs sm:rounded-xl border border-gray-200">
                <div class="max-w-3xl">
                    @livewire('profile.personal-configs', ['activeTab' => 'theme'])
                </div>
            </div>

            {{-- TAB 3: SECURITY & PASSKEYS --}}
            <div x-show="activeTab === 'security'" x-cloak class="space-y-6">
                {{-- WebAuthn Passkeys First Setup & Management --}}
                <div class="p-6 bg-white shadow-xs sm:rounded-xl border border-gray-200">
                    <div class="max-w-4xl">
                        @livewire('profile.personal-configs', ['activeTab' => 'security'])
                    </div>
                </div>

                {{-- Update Password Form --}}
                <div class="p-6 bg-white shadow-xs sm:rounded-xl border border-gray-200">
                    <div class="max-w-xl">
                        <div @submit="clearDirty()">
                            @include('profile.partials.update-password-form')
                        </div>
                    </div>
                </div>
            </div>

            {{-- TAB 4: NOTIFICATION PREFERENCES --}}
            <div x-show="activeTab === 'notifications'" x-cloak class="p-6 bg-white shadow-xs sm:rounded-xl border border-gray-200">
                <div class="max-w-5xl">
                    @livewire('notification-settings', ['activeTab' => 'personal'])
                </div>
            </div>
        </div>

        {{-- UNSAVED CHANGES CONFIRMATION MODAL --}}
        <div x-show="showUnsavedModal" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs">
            <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 space-y-4 border border-gray-200"
                 @click.away="cancelSwitch()">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-amber-100 text-amber-700 flex items-center justify-center text-lg font-bold shrink-0">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-gray-900">Unsaved Changes</h3>
                        <p class="text-xs text-gray-500 mt-0.5">
                            You have modified inputs in this section that are not yet saved.
                        </p>
                    </div>
                </div>

                <div class="text-xs text-gray-700 bg-amber-50/70 border border-amber-200 p-3 rounded-xl leading-relaxed">
                    If you leave this tab without clicking <strong>Save</strong>, your changes will be discarded.
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="button" @click="cancelSwitch()"
                            class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded-lg text-xs font-semibold transition">
                        Keep Editing
                    </button>
                    <button type="button" @click="confirmSwitch()"
                            class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-xs font-bold shadow-xs transition">
                        Discard & Leave Tab
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Passkey WebAuthn Script --}}
    <script src="/vendor/webauthn/webauthn.js"></script>
    <script>
        async function registerUserPasskey() {
            const alertBox = document.getElementById('passkey-reg-alert');
            const btn = document.getElementById('btn-register-passkey');
            if (alertBox) {
                alertBox.classList.add('hidden');
                alertBox.textContent = '';
            }

            if (window.location.hostname === '127.0.0.1') {
                if (alertBox) {
                    alertBox.className = 'p-3 rounded-lg text-xs font-semibold bg-amber-500 text-white';
                    alertBox.innerHTML = '<span>⚠️ WebAuthn passkeys require a domain name (not raw IP 127.0.0.1). Please <a href="' + window.location.href.replace('127.0.0.1', 'localhost') + '" class="underline font-bold text-white">click here to switch to http://localhost:8000</a> to register your passkey.</span>';
                    alertBox.classList.remove('hidden');
                }
                return;
            }

            if (typeof WebAuthn === 'undefined' || !WebAuthn.supportsWebAuthn()) {
                if (alertBox) {
                    alertBox.className = 'p-3 rounded-lg text-xs font-semibold bg-rose-500 text-white';
                    alertBox.textContent = 'WebAuthn is not supported by your browser or environment.';
                    alertBox.classList.remove('hidden');
                }
                return;
            }

            const originalText = btn ? btn.innerHTML : '';
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1"></i> Waiting for biometric / key prompt...';
            }

            try {
                const webauthn = new WebAuthn({
                    registerOptions: '/webauthn/register/options',
                    register: '/webauthn/register'
                });

                await webauthn.register();

                if (alertBox) {
                    alertBox.className = 'p-3 rounded-lg text-xs font-semibold bg-emerald-500 text-white';
                    alertBox.textContent = '✓ Device passkey registered successfully! You can now use it for quick sign-in.';
                    alertBox.classList.remove('hidden');
                }
                setTimeout(() => window.location.reload(), 1200);
            } catch (err) {
                console.error(err);
                if (alertBox) {
                    alertBox.className = 'p-3 rounded-lg text-xs font-semibold bg-rose-500 text-white';
                    alertBox.textContent = (err && err.message) ? err.message : 'Passkey registration cancelled or failed.';
                    alertBox.classList.remove('hidden');
                }
            } finally {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                }
            }
        }
    </script>
</x-app-layout>
