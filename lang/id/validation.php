<?php

/*
| Pesan validasi bahasa Indonesia.
| Hanya memuat aturan yang benar-benar dipakai aplikasi ini — sisanya
| jatuh ke bahasa Inggris bawaan Laravel (fallback_locale=en).
*/

return [
    'accepted' => 'Anda harus menyetujui :attribute.',
    'after' => ':attribute harus berisi tanggal setelah :date.',
    'boolean' => 'Isi :attribute harus true atau false.',
    'confirmed' => 'Konfirmasi :attribute tidak cocok.',
    'current_password' => 'Kata sandi yang Anda masukkan salah.',
    'date' => ':attribute bukan tanggal yang valid.',
    'digits' => ':attribute harus terdiri dari :digits digit.',
    'email' => 'Format :attribute tidak valid.',
    'file' => ':attribute harus berupa berkas.',
    'image' => ':attribute harus berupa gambar.',
    'in' => 'Pilihan :attribute tidak valid.',
    'integer' => ':attribute harus berupa angka bulat.',
    'max' => [
        'array' => ':attribute maksimal :max item.',
        'file' => 'Ukuran :attribute maksimal :max kilobyte.',
        'numeric' => ':attribute maksimal :max.',
        'string' => ':attribute maksimal :max karakter.',
    ],
    'mimes' => ':attribute harus berformat: :values.',
    'min' => [
        'array' => ':attribute minimal :min item.',
        'file' => 'Ukuran :attribute minimal :min kilobyte.',
        'numeric' => ':attribute minimal :min.',
        'string' => ':attribute minimal :min karakter.',
    ],
    'numeric' => ':attribute harus berupa angka.',
    'password' => [
        'letters' => ':attribute harus memuat minimal satu huruf.',
        'mixed' => ':attribute harus memuat huruf besar dan huruf kecil.',
        'numbers' => ':attribute harus memuat minimal satu angka.',
        'symbols' => ':attribute harus memuat minimal satu simbol.',
        'uncompromised' => ':attribute pernah bocor di kebocoran data. Pilih yang lain.',
    ],
    'required' => ':attribute wajib diisi.',
    'string' => ':attribute harus berupa teks.',
    'unique' => ':attribute sudah terdaftar.',
    'uploaded' => ':attribute gagal diunggah. Coba berkas yang lebih kecil.',

    'attributes' => [
        'name' => 'nama',
        'email' => 'email',
        'phone' => 'nomor telepon',
        'password' => 'kata sandi',
        'password_confirmation' => 'konfirmasi kata sandi',
        'current_password' => 'kata sandi saat ini',
        'role' => 'peran',
        'terms' => 'persetujuan syarat',
        'title' => 'judul',
        'category' => 'kategori',
        'summary' => 'ringkasan',
        'description' => 'deskripsi',
        'target_amount' => 'target dana',
        'deadline' => 'batas waktu',
        'cover' => 'foto sampul',
        'items' => 'rincian anggaran',
        'milestones' => 'tahap pencairan',
        'amount' => 'nominal',
        'donor_name' => 'nama donatur',
        'donor_email' => 'email donatur',
        'message' => 'pesan',
        'note' => 'catatan',
        'decision' => 'keputusan',
        'reference' => 'nomor transaksi',
        'code' => 'kode verifikasi',
        'identity_number' => 'nomor identitas',
        'identity_document' => 'dokumen identitas',
        'organization' => 'lembaga',
    ],
];
