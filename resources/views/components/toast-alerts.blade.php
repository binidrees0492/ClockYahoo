{{--
    Toast Alerts Component (<x-toast-alerts />)
    UX Standard: Top-Right Floating Alerts with Bootstrap Color Palette,
    proper Error/Success/Warning/Info categorization, smooth slide-in,
    and automatic dismissal without causing page reflow or layout shift.
--}}
<div x-data="{
        alerts: [],
        normalizeType(type, msg = '') {
            if (typeof type === 'number') {
                if (type >= 200 && type < 300) return 'success';
                if (type >= 400 && type < 600) return 'danger';
                if (type >= 300 && type < 400) return 'warning';
                return 'info';
            }
            let t = String(type || '').toLowerCase().trim();
            if (['error', 'danger', 'fail', 'failed', 'critical', '500', '404', '422', '403', '401', '535'].includes(t)) {
                return 'danger';
            }
            if (['warning', 'warn', 'alert', '429'].includes(t)) {
                return 'warning';
            }
            if (['info', 'notice', 'status', 'default'].includes(t)) {
                return 'info';
            }
            // Check text heuristics if type is unspecified
            let text = String(msg || '').toLowerCase();
            if (text.includes('error:') || text.includes('failed:') || text.includes('could not connect') || text.includes('exception')) {
                return 'danger';
            }
            if (text.includes('warning:') || text.includes('caution:')) {
                return 'warning';
            }
            return 'success';
        },
        add(msg, type = 'success') {
            if (!msg) return;
            let text = typeof msg === 'object' ? (msg.message || msg.text || msg.msg || '') : String(msg);
            if (!text || !text.trim()) return;
            
            let cleanType = this.normalizeType(type, text);
            let id = Date.now() + Math.random();
            let duration = (cleanType === 'danger' || text.length > 80 || text.includes('Error:') || text.includes('failed:')) ? 7500 : 4500;
            
            // Prevent exact duplicate spam within 1 second
            let duplicate = this.alerts.find(a => a.msg === text.trim() && (Date.now() - a.timestamp) < 1200);
            if (duplicate) return;

            this.alerts.push({
                id: id,
                msg: text.trim(),
                type: cleanType,
                timestamp: Date.now()
            });

            setTimeout(() => {
                this.dismiss(id);
            }, duration);
        },
        dismiss(id) {
            this.alerts = this.alerts.filter(a => a.id !== id);
        },
        handleEvent(detail) {
            if (!detail) return;
            if (Array.isArray(detail)) detail = detail[0] || {};
            let text = typeof detail === 'object' ? (detail.message || detail.text || detail.msg || '') : String(detail);
            let type = (typeof detail === 'object' && detail.type) ? detail.type : 'success';
            this.add(text, type);
        }
    }"
    @toast.window="handleEvent($event.detail)"
    @notify.window="handleEvent($event.detail)"
    @alert.window="handleEvent($event.detail)"
    x-init="
        window.showToast = (msg, type = 'success') => add(msg, type);
        window.showAlert = (msg, type = 'success') => add(msg, type);
        window.showNotification = (msg, type = 'success') => add(msg, type);

        @if(session('message')) add(@js(session('message')), 'success'); @endif
        @if(session('success')) add(@js(session('success')), 'success'); @endif
        @if(session('error')) add(@js(session('error')), 'danger'); @endif
        @if(session('status')) add(@js(session('status')), 'info'); @endif
        @if(session('warning')) add(@js(session('warning')), 'warning'); @endif

        const registerLivewireHook = () => {
            if (window.Livewire && !window._cvToastHookRegistered) {
                window._cvToastHookRegistered = true;
                Livewire.hook('commit', ({ component, succeed }) => {
                    succeed(() => {
                        let data = component.snapshot?.data || {};
                        if (data.toastMessage && data.toastMessage !== component.__prevToastMessage) {
                            component.__prevToastMessage = data.toastMessage;
                            window.showToast(data.toastMessage, data.toastType || 'success');
                        }
                        if (data.validationMessage && data.validationMessage !== component.__prevValidationMessage) {
                            component.__prevValidationMessage = data.validationMessage;
                            window.showToast(data.validationMessage, data.validationStatus || 'info');
                        }
                        if (data.testMessage && data.testMessage !== component.__prevTestMessage) {
                            component.__prevTestMessage = data.testMessage;
                            window.showToast(data.testMessage, (data.testStatus === 'success' ? 'success' : 'danger'));
                        }
                        if (data.settingsTestMessage && data.settingsTestMessage !== component.__prevSettingsTestMessage) {
                            component.__prevSettingsTestMessage = data.settingsTestMessage;
                            window.showToast(data.settingsTestMessage, (data.settingsTestStatus === 'success' ? 'success' : 'danger'));
                        }
                    });
                });
            }
        };
        registerLivewireHook();
        document.addEventListener('livewire:init', registerLivewireHook);
    "
    style="position: fixed; top: 1.25rem; right: 1.25rem; z-index: 999999; display: flex; flex-direction: column; gap: 0.65rem; pointer-events: none; width: calc(100vw - 2.5rem); max-width: 420px;">
    
    <template x-for="a in alerts" :key="a.id">
        <div x-transition:enter="transition ease-out duration-300 transform"
             x-transition:enter-start="opacity-0 translate-x-8 scale-95"
             x-transition:enter-end="opacity-100 translate-x-0 scale-100"
             x-transition:leave="transition ease-in duration-200 transform"
             x-transition:leave-start="opacity-100 translate-x-0 scale-100"
             x-transition:leave-end="opacity-0 translate-x-8 scale-95"
             :style="{
                 pointerEvents: 'auto',
                 display: 'flex',
                 alignItems: 'flex-start',
                 justifyContent: 'space-between',
                 gap: '12px',
                 padding: '12px 14px',
                 borderRadius: '8px',
                 fontSize: '13px',
                 lineHeight: '1.45',
                 fontWeight: '500',
                 boxShadow: '0 10px 25px -5px rgba(0,0,0,0.18), 0 8px 10px -6px rgba(0,0,0,0.1)',
                 border: a.type === 'danger' ? '1px solid #f5c2c7' : (a.type === 'warning' ? '1px solid #ffecb5' : (a.type === 'info' ? '1px solid #b6effb' : '1px solid #badbcc')),
                 borderLeft: a.type === 'danger' ? '5px solid #dc3545' : (a.type === 'warning' ? '5px solid #ffc107' : (a.type === 'info' ? '5px solid #0dcaf0' : '5px solid #198754')),
                 backgroundColor: a.type === 'danger' ? '#f8d7da' : (a.type === 'warning' ? '#fff3cd' : (a.type === 'info' ? '#cff4fc' : '#d1e7dd')),
                 color: a.type === 'danger' ? '#842029' : (a.type === 'warning' ? '#664d03' : (a.type === 'info' ? '#055160' : '#0f5132')),
                 wordBreak: 'break-word',
                 transition: 'all 0.25s ease'
             }"
             role="alert">

            <div style="display: flex; align-items: flex-start; gap: 10px; flex: 1; min-width: 0;">
                {{-- Dynamic Icon based on type --}}
                <div style="flex-shrink: 0; margin-top: 2px;">
                    <template x-if="a.type === 'success'">
                        <svg style="width: 18px; height: 18px; color: #198754;" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                    </template>
                    <template x-if="a.type === 'danger'">
                        <svg style="width: 18px; height: 18px; color: #dc3545;" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                    </template>
                    <template x-if="a.type === 'warning'">
                        <svg style="width: 18px; height: 18px; color: #b78103;" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                    </template>
                    <template x-if="a.type === 'info'">
                        <svg style="width: 18px; height: 18px; color: #0aa2c0;" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                    </template>
                </div>

                {{-- Alert Message --}}
                <div style="flex: 1; min-width: 0;">
                    <span x-text="a.msg"></span>
                </div>
            </div>

            {{-- Close Button --}}
            <button type="button" @click="dismiss(a.id)"
                    style="flex-shrink: 0; background: transparent; border: none; font-size: 20px; line-height: 1; padding: 0 4px; cursor: pointer; opacity: 0.6; transition: opacity 0.15s;"
                    onmouseover="this.style.opacity = '1'"
                    onmouseout="this.style.opacity = '0.6'"
                    :style="{ color: a.type === 'danger' ? '#842029' : (a.type === 'warning' ? '#664d03' : (a.type === 'info' ? '#055160' : '#0f5132')) }"
                    aria-label="Close" title="Dismiss">&times;</button>
        </div>
    </template>
</div>
