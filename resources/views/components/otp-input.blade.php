@props([
    'purpose',
    'label' => 'Kode Verifikasi Email',
    'name' => 'otp_code',
])

<div x-data="{
    loading: false,
    countdown: 0,
    sentMessage: '',
    errorMessage: '',
    timer: null,
    sendOtp() {
        if (this.countdown > 0 || this.loading) return;
        this.loading = true;
        this.errorMessage = '';
        this.sentMessage = '';

        fetch('{{ route('otp.send') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ purpose: '{{ $purpose }}' })
        })
        .then(async (res) => {
            const data = await res.json();
            if (!res.ok) {
                throw new Error(data.message || (data.errors && Object.values(data.errors)[0][0]) || 'Gagal mengirim kode.');
            }
            return data;
        })
        .then((data) => {
            this.sentMessage = data.message || 'Kode OTP telah dikirim ke email Anda.';
            this.countdown = 60;
            if (this.timer) clearInterval(this.timer);
            this.timer = setInterval(() => {
                this.countdown--;
                if (this.countdown <= 0) clearInterval(this.timer);
            }, 1000);
        })
        .catch((err) => {
            this.errorMessage = err.message;
        })
        .finally(() => {
            this.loading = false;
        });
    }
}" class="space-y-1.5">
    <div class="flex items-center justify-between gap-2">
        <label for="{{ $name }}" class="dt-label !mb-0">{{ $label }}</label>
        <button type="button" @click="sendOtp" :disabled="countdown > 0 || loading"
                class="text-xs font-semibold text-brand-700 hover:text-brand-800 disabled:text-ink-400 disabled:cursor-not-allowed">
            <span x-show="loading">Mengirim...</span>
            <span x-show="!loading && countdown === 0">Kirim Kode ke Email</span>
            <span x-show="!loading && countdown > 0" x-text="'Kirim ulang (' + countdown + 's)'"></span>
        </button>
    </div>

    <input id="{{ $name }}" name="{{ $name }}" type="text" required maxlength="6" pattern="[0-9]{6}" inputmode="numeric"
           placeholder="6 digit angka" class="dt-input font-mono tracking-widest text-center text-lg font-bold"
           autocomplete="one-time-code">

    <p class="text-xs text-brand-700 font-medium" x-show="sentMessage" x-text="sentMessage" x-cloak></p>
    <p class="text-xs text-rose-600 font-medium" x-show="errorMessage" x-text="errorMessage" x-cloak></p>
    @error($name)
        <p class="dt-error">{{ $message }}</p>
    @enderror
</div>
