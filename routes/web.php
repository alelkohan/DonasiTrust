<?php

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\CampaignReviewController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\UserVerificationController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\CampaignBrowseController;
use App\Http\Controllers\DonaturDashboardController;
use App\Http\Controllers\DonationController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PaymentWebhookController;
use App\Http\Controllers\Pengaju\CampaignController;
use App\Http\Controllers\Pengaju\DashboardController as PengajuDashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\SecureFileController;
use App\Http\Controllers\TransparencyController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Halaman publik
|--------------------------------------------------------------------------
*/

Route::get('/', HomeController::class)->name('home');

Route::get('/kampanye', [CampaignBrowseController::class, 'index'])->name('kampanye.index');
Route::get('/kampanye/{campaign}', [CampaignBrowseController::class, 'show'])->name('kampanye.show');
Route::get('/kampanye/{campaign}/donatur', [CampaignBrowseController::class, 'donors'])->name('kampanye.donors');
Route::get('/kampanye/{campaign}/transparansi', [CampaignBrowseController::class, 'transparency'])
    ->name('kampanye.transparansi');

Route::get('/transparansi', TransparencyController::class)->name('transparansi');

// Donasi terbuka untuk tamu. Dibatasi lajunya agar tidak dipakai membanjiri
// tabel transaksi dengan donasi pending palsu.
Route::post('/kampanye/{campaign}/donasi', [DonationController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('donasi.store');

Route::get('/donasi/{donation}/pembayaran', [DonationController::class, 'checkout'])->name('donasi.checkout');
Route::get('/donasi/{donation}/status', [DonationController::class, 'status'])->name('donasi.status');
Route::post('/donasi/{donation}/simulasi-bayar', [DonationController::class, 'simulatePayment'])
    ->middleware('throttle:20,1')
    ->name('donasi.simulasi');
Route::post('/donasi/{donation}/batal', [DonationController::class, 'cancel'])->name('donasi.cancel');

Route::get('/kuitansi/{donation}', [ReceiptController::class, 'show'])->name('kuitansi.show');
Route::get('/verifikasi', [ReceiptController::class, 'form'])->name('verifikasi.form');
Route::post('/verifikasi', [ReceiptController::class, 'check'])
    ->middleware('throttle:20,1')
    ->name('verifikasi.check');

// Bukti nota bersifat publik — bagian dari janji transparansi.
Route::get('/bukti/lpj/{expense}', [SecureFileController::class, 'receipt'])->name('berkas.lpj');

// Webhook gateway (server-ke-server, dikecualikan dari CSRF di bootstrap/app.php).
Route::post('/webhook/payment/{driver}', PaymentWebhookController::class)->name('webhook.payment');

/*
|--------------------------------------------------------------------------
| Autentikasi
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('/masuk', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/masuk', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:10,1');
    Route::get('/daftar', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/daftar', [RegisteredUserController::class, 'store'])->middleware('throttle:10,1');

    // Google Socialite OAuth
    Route::get('/auth/google', [GoogleAuthController::class, 'redirectToGoogle'])->name('auth.google');
    Route::post('/auth/google/mock', [GoogleAuthController::class, 'mockLogin'])->name('auth.google.mock');
    Route::get('/auth/google/callback', [GoogleAuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');
});

Route::post('/keluar', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')->name('logout');

/*
|--------------------------------------------------------------------------
| Area terautentikasi
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    Route::get('/profil', [ProfileController::class, 'edit'])->name('profil.edit');
    Route::put('/profil', [ProfileController::class, 'update'])->name('profil.update');
    Route::put('/profil/kata-sandi', [ProfileController::class, 'updatePassword'])->name('profil.password');
    Route::post('/otp/kirim', [\App\Http\Controllers\OtpController::class, 'send'])
        ->middleware('throttle:10,1')->name('otp.send');


    Route::get('/verifikasi-identitas', fn () => view('profile.verification', ['user' => auth()->user()]))
        ->name('verifikasi.identitas');
    Route::post('/verifikasi-identitas', [ProfileController::class, 'submitVerification'])
        ->middleware('throttle:5,10')
        ->name('verifikasi.identitas.store');

    Route::get('/dashboard', DonaturDashboardController::class)->name('donatur.dashboard');

    Route::get('/berkas/identitas/{user}', [SecureFileController::class, 'identity'])->name('berkas.identitas');
    Route::get('/berkas/pencairan/{disbursement}', [SecureFileController::class, 'disbursement'])
        ->name('berkas.pencairan');
});

/*
|--------------------------------------------------------------------------
| Pengaju kampanye
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:pengaju'])->prefix('pengaju')->name('pengaju.')->group(function () {
    Route::get('/', PengajuDashboardController::class)->name('dashboard');

    Route::get('/kampanye', [CampaignController::class, 'index'])->name('kampanye.index');
    Route::get('/kampanye/baru', [CampaignController::class, 'create'])->name('kampanye.create');
    Route::post('/kampanye', [CampaignController::class, 'store'])->name('kampanye.store');
    Route::get('/kampanye/{campaign}/ubah', [CampaignController::class, 'edit'])->name('kampanye.edit');
    Route::put('/kampanye/{campaign}', [CampaignController::class, 'update'])->name('kampanye.update');
    Route::post('/kampanye/{campaign}/ajukan', [CampaignController::class, 'submit'])->name('kampanye.submit');
    Route::delete('/kampanye/{campaign}', [CampaignController::class, 'destroy'])->name('kampanye.destroy');
    
    // Kelola tahapan & pencairan
    Route::get('/kampanye/{campaign}/tahapan', [\App\Http\Controllers\Pengaju\MilestoneController::class, 'index'])->name('kampanye.milestones');
    Route::post('/kampanye/{campaign}/tahapan/{milestone}/pencairan', [\App\Http\Controllers\Pengaju\MilestoneController::class, 'requestDisbursement'])->name('kampanye.disbursement.request');
    Route::post('/kampanye/{campaign}/tahapan/{milestone}/lpj', [\App\Http\Controllers\Pengaju\MilestoneController::class, 'submitExpenseReport'])->name('kampanye.expense.submit');
});

/*
|--------------------------------------------------------------------------
| Admin
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', AdminDashboardController::class)->name('dashboard');

    Route::get('/kampanye', [CampaignReviewController::class, 'index'])->name('kampanye.index');
    Route::get('/kampanye/{campaign}', [CampaignReviewController::class, 'show'])->name('kampanye.show');
    Route::post('/kampanye/{campaign}/setujui', [CampaignReviewController::class, 'approve'])->name('kampanye.approve');
    Route::post('/kampanye/{campaign}/tolak', [CampaignReviewController::class, 'reject'])->name('kampanye.reject');
    Route::delete('/kampanye/{campaign}', [CampaignReviewController::class, 'destroy'])->name('kampanye.destroy');

    Route::get('/pencairan', [\App\Http\Controllers\Admin\DisbursementController::class, 'index'])->name('pencairan.index');
    Route::get('/pencairan/{disbursement}', [\App\Http\Controllers\Admin\DisbursementController::class, 'show'])->name('pencairan.show');
    Route::post('/pencairan/{disbursement}/approve', [\App\Http\Controllers\Admin\DisbursementController::class, 'approve'])->name('pencairan.approve');
    Route::post('/pencairan/{disbursement}/reject', [\App\Http\Controllers\Admin\DisbursementController::class, 'reject'])->name('pencairan.reject');
    Route::post('/pencairan/{disbursement}/release', [\App\Http\Controllers\Admin\DisbursementController::class, 'release'])->name('pencairan.release');

    Route::get('/lpj', [\App\Http\Controllers\Admin\ExpenseReportController::class, 'index'])->name('lpj.index');
    Route::get('/lpj/{expense}', [\App\Http\Controllers\Admin\ExpenseReportController::class, 'show'])->name('lpj.show');
    Route::post('/lpj/{expense}/verify', [\App\Http\Controllers\Admin\ExpenseReportController::class, 'verify'])->name('lpj.verify');
    Route::post('/lpj/{expense}/reject', [\App\Http\Controllers\Admin\ExpenseReportController::class, 'reject'])->name('lpj.reject');

    Route::get('/pengguna', [UserVerificationController::class, 'index'])->name('pengguna.index');
    Route::get('/pengguna/{user}', [UserVerificationController::class, 'show'])->name('pengguna.show');
    Route::post('/pengguna/{user}/putuskan', [UserVerificationController::class, 'decide'])->name('pengguna.decide');

    Route::get('/jejak-audit', [AuditLogController::class, 'index'])->name('audit.index');
});
