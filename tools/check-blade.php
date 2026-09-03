<?php

/**
 * Pemeriksa Blade sederhana (tanpa perlu vendor/).
 *
 * Dijalankan sebelum `composer install` tersedia, untuk menangkap dua kelas
 * kesalahan yang paling sering lolos saat menulis template:
 *  1. Direktif yang tidak seimbang (@if tanpa @endif, dst).
 *  2. Ekspresi PHP di dalam {{ }}, @if(...), @php ... @endphp yang salah sintaks.
 *
 * Ini BUKAN pengganti menjalankan aplikasi — hanya jaring pengaman awal.
 *
 * Pakai: php tools/check-blade.php resources/views
 */
$root = $argv[1] ?? __DIR__.'/../resources/views';

$pairs = [
    'if' => 'endif', 'foreach' => 'endforeach', 'forelse' => 'endforelse',
    'for' => 'endfor', 'while' => 'endwhile', 'switch' => 'endswitch',
    'section' => 'endsection', 'push' => 'endpush', 'prepend' => 'endprepend',
    'once' => 'endonce', 'verbatim' => 'endverbatim', 'error' => 'enderror',
    'isset' => 'endisset', 'empty' => 'endempty', 'unless' => 'endunless',
    'auth' => 'endauth', 'guest' => 'endguest', 'can' => 'endcan',
];

$problems = [];
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));

foreach ($files as $file) {
    if (! $file->isFile() || ! str_ends_with($file->getFilename(), '.blade.php')) {
        continue;
    }

    $path = $file->getPathname();
    $src = file_get_contents($path);

    // Buang komentar Blade supaya tidak ikut dihitung.
    $clean = preg_replace('/\{\{--.*?--\}\}/s', '', $src);

    // --- 1. Keseimbangan direktif ---
    foreach ($pairs as $open => $close) {
        // @empty punya dua arti: blok (@empty/@endempty) DAN pemisah di dalam
        // @forelse. Kalau file ini memakai @forelse, lewati pemeriksaannya
        // supaya tidak melaporkan ketidakseimbangan palsu.
        if ($open === 'empty' && preg_match('/@forelse\b/', $clean)) {
            continue;
        }

        // @section('x') satu baris (tanpa @endsection) sah, jadi dilewati.
        if ($open === 'section') {
            $openCount = preg_match_all('/@section\s*\([^)]*\)(?!\s*$)/m', $clean);
            $openCount = preg_match_all('/@section\s*\(\s*[\'"][^\'"]*[\'"]\s*\)\s*$/m', $clean);
        } else {
            $openCount = preg_match_all('/@'.$open.'\b/', $clean);
        }

        $closeCount = preg_match_all('/@'.$close.'\b/', $clean);

        if ($openCount !== $closeCount) {
            $problems[] = sprintf(
                '%s: @%s (%d) tidak seimbang dengan @%s (%d)',
                $path, $open, $openCount, $close, $closeCount
            );
        }
    }

    // --- 2. Sintaks ekspresi PHP ---
    $snippets = [];

    if (preg_match_all('/\{\{(.+?)\}\}/s', $clean, $m)) {
        foreach ($m[1] as $expr) {
            $snippets[] = 'echo '.trim($expr).';';
        }
    }

    if (preg_match_all('/\{!!(.+?)!!\}/s', $clean, $m)) {
        foreach ($m[1] as $expr) {
            $snippets[] = 'echo '.trim($expr).';';
        }
    }

    if (preg_match_all('/@php\b(?!\s*\()(.*?)@endphp/s', $clean, $m)) {
        foreach ($m[1] as $block) {
            $snippets[] = $block;
        }
    }

    if (preg_match_all('/@php\s*\((.+?)\)\s*$/m', $clean, $m)) {
        foreach ($m[1] as $expr) {
            $snippets[] = $expr.';';
        }
    }

    foreach (['if', 'elseif', 'unless', 'foreach', 'for', 'while', 'switch', 'class', 'checked', 'selected', 'continue'] as $directive) {
        if (preg_match_all('/@'.$directive.'\s*(\((?:[^()]|(?1))*\))/s', $clean, $m)) {
            foreach ($m[1] as $args) {
                $inner = substr($args, 1, -1);

                $snippets[] = match ($directive) {
                    'foreach' => 'foreach ('.$inner.') {}',
                    'for' => 'for ('.$inner.') {}',
                    'while' => 'while ('.$inner.') {}',
                    'switch' => 'switch ('.$inner.') { default: }',
                    'if', 'elseif', 'unless' => 'if ('.$inner.') {}',
                    default => 'echo '.$inner.';',
                };
            }
        }
    }

    foreach ($snippets as $i => $snippet) {
        $tmp = tempnam(sys_get_temp_dir(), 'blade').'.php';
        file_put_contents($tmp, "<?php\n".$snippet."\n");
        exec('php -l '.escapeshellarg($tmp).' 2>&1', $out, $code);
        unlink($tmp);

        if ($code !== 0) {
            $problems[] = sprintf(
                "%s: ekspresi #%d gagal parse\n    %s\n    %s",
                $path, $i + 1, trim(preg_replace('/\s+/', ' ', $snippet)),
                trim(str_replace($tmp, '(potongan)', implode(' ', $out)))
            );
        }

        $out = [];
    }
}

if ($problems) {
    echo count($problems)." masalah ditemukan:\n\n";
    echo implode("\n", $problems)."\n";
    exit(1);
}

echo "Semua template Blade lolos pemeriksaan awal.\n";
