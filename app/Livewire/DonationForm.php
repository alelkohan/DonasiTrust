<?php

namespace App\Livewire;

use App\Models\Campaign;
use App\Services\DonationService;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Form donasi interaktif.
 *
 * Dipakai Livewire (bukan Blade biasa) karena nominal perlu diformat dan
 * divalidasi sambil diketik, dan ringkasan pembayaran ikut berubah mengikuti
 * nominal — tanpa perlu menulis endpoint API terpisah.
 */
class DonationForm extends Component
{
    public Campaign $campaign;

    public string $amount = '';

    public string $donorName = '';

    public string $donorEmail = '';

    /** Sengaja TIDAK dinamai $message: nama itu dipakai direktif @error Blade. */
    public string $note = '';

    public bool $isAnonymous = false;

    public function mount(Campaign $campaign): void
    {
        $this->campaign = $campaign;

        if ($user = auth()->user()) {
            $this->donorName = $user->name;
            $this->donorEmail = $user->email;
        }
    }

    protected function rules(): array
    {
        return [
            'amount' => ['required'],
            'donorName' => ['nullable', 'string', 'max:120'],
            'donorEmail' => [auth()->check() ? 'nullable' : 'required', 'email', 'max:255'],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }

    protected function messages(): array
    {
        return [
            'amount.required' => 'Pilih atau isi nominal donasi.',
            'donorEmail.required' => 'Email diperlukan untuk mengirim kuitansi digital.',
            'donorEmail.email' => 'Format email belum benar.',
            'note.max' => 'Pesan dukungan maksimal 500 karakter.',
        ];
    }

    /** Nominal sebagai integer, terlepas dari titik pemisah yang diketik user. */
    #[Computed]
    public function amountValue(): int
    {
        return (int) preg_replace('/\D/', '', $this->amount);
    }

    #[Computed]
    public function isValidAmount(): bool
    {
        return $this->amountValue >= (int) config('donasi.min_donation')
            && $this->amountValue <= (int) config('donasi.max_donation');
    }

    public function setAmount(int $value): void
    {
        if ($this->amountValue === $value) {
            $this->amount = '';
        } else {
            $this->amount = number_format($value, 0, ',', '.');
        }
        $this->resetValidation('amount');
    }

    public function updatedAmount(): void
    {
        // Rapikan tampilan jadi 1.500.000 sambil user mengetik.
        $this->amount = $this->amountValue > 0
            ? number_format($this->amountValue, 0, ',', '.')
            : '';
    }

    public function submit(DonationService $donations)
    {
        $this->validate();

        if (! $this->isValidAmount) {
            $this->addError('amount', 'Nominal minimal '
                .rupiah((int) config('donasi.min_donation')).' dan maksimal '
                .rupiah((int) config('donasi.max_donation')).'.');

            return null;
        }

        $donation = $donations->create($this->campaign, [
            'amount' => $this->amountValue,
            'donor_name' => $this->donorName ?: null,
            'donor_email' => $this->donorEmail ?: null,
            'message' => $this->note ?: null,
            'is_anonymous' => $this->isAnonymous,
        ]);

        return $this->redirectRoute('donasi.checkout', ['donation' => $donation->reference]);
    }

    public function render()
    {
        return view('livewire.donation-form', [
            'quickAmounts' => config('donasi.quick_amounts'),
        ]);
    }
}
