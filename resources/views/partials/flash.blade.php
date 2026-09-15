@php
    $initialToasts = [];
    if (session('status')) {
        $initialToasts[] = [
            'id' => 'session_status_' . uniqid(),
            'type' => 'success',
            'title' => 'Berhasil',
            'message' => (string) session('status'),
            'duration' => 5000,
        ];
    }
    if (session('success')) {
        $initialToasts[] = [
            'id' => 'session_success_' . uniqid(),
            'type' => 'success',
            'title' => 'Berhasil',
            'message' => (string) session('success'),
            'duration' => 5000,
        ];
    }
    if (session('warning')) {
        $initialToasts[] = [
            'id' => 'session_warning_' . uniqid(),
            'type' => 'warning',
            'title' => 'Peringatan',
            'message' => (string) session('warning'),
            'duration' => 6000,
        ];
    }
    if (session('error')) {
        $initialToasts[] = [
            'id' => 'session_error_' . uniqid(),
            'type' => 'error',
            'title' => 'Gagal',
            'message' => (string) session('error'),
            'duration' => 7000,
        ];
    }
    if (session('info')) {
        $initialToasts[] = [
            'id' => 'session_info_' . uniqid(),
            'type' => 'info',
            'title' => 'Informasi',
            'message' => (string) session('info'),
            'duration' => 5000,
        ];
    }
    if (isset($errors) && $errors->any()) {
        $initialToasts[] = [
            'id' => 'session_errors_' . uniqid(),
            'type' => 'error',
            'title' => 'Ada ' . $errors->count() . ' hal yang perlu diperbaiki',
            'message' => $errors->first(),
            'errors' => $errors->all(),
            'duration' => 8000,
        ];
    }
@endphp

<div
    x-data="toastManager(@js(['toasts' => $initialToasts]))"
    x-cloak
    class="fixed top-20 right-4 sm:right-6 z-[99999] pointer-events-none flex flex-col gap-3 w-[calc(100vw-2rem)] sm:w-[420px] max-w-full print:hidden"
    aria-live="polite"
    aria-atomic="true"
>
    <template x-for="toast in toasts" :key="toast.id">
        <div
            x-show="toast.visible"
            x-transition:enter="transform ease-out duration-350 transition"
            x-transition:enter-start="translate-y-3 opacity-0 sm:translate-y-0 sm:translate-x-8 scale-90"
            x-transition:enter-end="translate-y-0 opacity-100 sm:translate-x-0 scale-100"
            x-transition:leave="transform ease-in duration-300 transition"
            x-transition:leave-start="opacity-100 scale-100 sm:translate-x-0"
            x-transition:leave-end="opacity-0 scale-90 sm:translate-x-8"
            @mouseenter="pause(toast.id)"
            @mouseleave="resume(toast.id)"
            class="dt-toast pointer-events-auto relative overflow-hidden rounded-2xl border backdrop-blur-2xl shadow-2xl transition-all duration-200"
            :class="{
                'bg-[#181528]/95 border-[#99ff04]/40 shadow-[#99ff04]/10 text-white': toast.type === 'success',
                'bg-[#181528]/95 border-rose-500/40 shadow-rose-500/10 text-white': toast.type === 'error',
                'bg-[#181528]/95 border-amber-500/40 shadow-amber-500/10 text-white': toast.type === 'warning',
                'bg-[#181528]/95 border-sky-500/40 shadow-sky-500/10 text-white': toast.type === 'info'
            }"
            role="alert"
        >
            <div class="flex items-start gap-3.5 p-4">
                {{-- Icon Indicator --}}
                <div class="shrink-0 mt-0.5">
                    {{-- Success Icon --}}
                    <template x-if="toast.type === 'success'">
                        <span class="grid h-8 w-8 place-items-center rounded-xl bg-[#99ff04]/15 border border-[#99ff04]/30 text-[#99ff04]">
                            <svg class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="9"/>
                                <path d="m8.5 12 2.4 2.4 4.6-4.8"/>
                            </svg>
                        </span>
                    </template>

                    {{-- Error Icon --}}
                    <template x-if="toast.type === 'error'">
                        <span class="grid h-8 w-8 place-items-center rounded-xl bg-rose-500/15 border border-rose-500/30 text-rose-400">
                            <svg class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="9"/>
                                <path d="m9 9 6 6m0-6-6 6"/>
                            </svg>
                        </span>
                    </template>

                    {{-- Warning Icon --}}
                    <template x-if="toast.type === 'warning'">
                        <span class="grid h-8 w-8 place-items-center rounded-xl bg-amber-500/15 border border-amber-500/30 text-amber-400">
                            <svg class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 9v4.5m0 3.5h.01M10.3 4.2 2.9 17.1a1.9 1.9 0 0 0 1.7 2.9h14.8a1.9 1.9 0 0 0 1.7-2.9L13.7 4.2a1.9 1.9 0 0 0-3.4 0Z"/>
                            </svg>
                        </span>
                    </template>

                    {{-- Info Icon --}}
                    <template x-if="toast.type === 'info'">
                        <span class="grid h-8 w-8 place-items-center rounded-xl bg-sky-500/15 border border-sky-500/30 text-sky-400">
                            <svg class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="9"/>
                                <path d="M12 16v-4m0-4h.01"/>
                            </svg>
                        </span>
                    </template>
                </div>

                {{-- Message Body --}}
                <div class="flex-1 min-w-0 pr-1">
                    <div class="flex items-center gap-2">
                        <span
                            class="text-[11px] font-black tracking-wider uppercase"
                            :class="{
                                'text-[#99ff04]': toast.type === 'success',
                                'text-rose-400': toast.type === 'error',
                                'text-amber-400': toast.type === 'warning',
                                'text-sky-400': toast.type === 'info'
                            }"
                            x-text="toast.title"
                        ></span>
                    </div>

                    <p class="mt-0.5 text-xs sm:text-sm font-semibold text-slate-100 leading-snug" x-text="toast.message"></p>

                    {{-- Multiple error list if present --}}
                    <template x-if="toast.errors && toast.errors.length > 1">
                        <ul class="mt-2 space-y-1 pl-4 list-disc text-xs text-rose-300/90 border-t border-rose-500/20 pt-2">
                            <template x-for="(err, idx) in toast.errors" :key="idx">
                                <li x-text="err"></li>
                            </template>
                        </ul>
                    </template>
                </div>

                {{-- Close Button --}}
                <button
                    type="button"
                    @click="removeToast(toast.id)"
                    class="shrink-0 -mr-1 -mt-1 rounded-xl p-1.5 text-slate-400 hover:text-white hover:bg-white/10 active:scale-95 transition-all cursor-pointer"
                    aria-label="Tutup notifikasi"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Animated Progress Bar --}}
            <div class="h-1 w-full bg-white/5">
                <div
                    class="h-full transition-all duration-75"
                    :class="{
                        'bg-[#99ff04]': toast.type === 'success',
                        'bg-rose-500': toast.type === 'error',
                        'bg-amber-400': toast.type === 'warning',
                        'bg-sky-400': toast.type === 'info'
                    }"
                    :style="'width: ' + toast.progress + '%'"
                ></div>
            </div>
        </div>
    </template>
</div>

<script>
    (function() {
        function registerToastManager() {
            if (window.Alpine && !window.Alpine.data('toastManager')) {
                window.Alpine.data('toastManager', (initialData = {}) => ({
                    toasts: [],
                    init() {
                        if (initialData && Array.isArray(initialData.toasts)) {
                            initialData.toasts.forEach(t => this.addToast(t));
                        }

                        const handleEvent = (e) => {
                            const d = e.detail || {};
                            if (typeof d === 'string') {
                                this.addToast({ message: d, type: 'info' });
                            } else {
                                this.addToast(d);
                            }
                        };

                        window.addEventListener('toast', handleEvent);
                        window.addEventListener('notify', handleEvent);
                    },
                    addToast(t) {
                        if (!t || (!t.message && !t.errors && !t.title)) return;

                        const id = t.id || 'toast_' + Date.now() + '_' + Math.random().toString(36).substring(2, 7);
                        if (this.toasts.some(item => item.id === id)) return;

                        const duration = t.duration || (t.errors && t.errors.length > 1 ? 8000 : 5000);
                        const toast = {
                            id,
                            type: t.type || 'success',
                            title: t.title || (t.type === 'error' ? 'Gagal' : (t.type === 'warning' ? 'Peringatan' : (t.type === 'info' ? 'Informasi' : 'Berhasil'))),
                            message: t.message || '',
                            errors: Array.isArray(t.errors) ? t.errors : null,
                            duration,
                            progress: 100,
                            paused: false,
                            visible: true,
                            interval: null
                        };

                        this.toasts.push(toast);

                        const step = 50;
                        const decrement = (step / duration) * 100;

                        toast.interval = setInterval(() => {
                            if (!toast.paused) {
                                toast.progress -= decrement;
                                if (toast.progress <= 0) {
                                    this.removeToast(id);
                                }
                            }
                        }, step);
                    },
                    pause(id) {
                        const item = this.toasts.find(t => t.id === id);
                        if (item) item.paused = true;
                    },
                    resume(id) {
                        const item = this.toasts.find(t => t.id === id);
                        if (item) item.paused = false;
                    },
                    removeToast(id) {
                        const item = this.toasts.find(t => t.id === id);
                        if (item) {
                            if (item.interval) {
                                clearInterval(item.interval);
                            }
                            item.visible = false;
                            setTimeout(() => {
                                this.toasts = this.toasts.filter(t => t.id !== id);
                            }, 350);
                        }
                    }
                }));
            }
        }

        document.addEventListener('alpine:init', registerToastManager);
        document.addEventListener('DOMContentLoaded', registerToastManager);
        document.addEventListener('livewire:navigated', registerToastManager);
        registerToastManager();
    })();
</script>
