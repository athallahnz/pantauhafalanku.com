<?php

declare(strict_types=1);

const PATCH_NAME = 'score-guard-2026-07-14';

function failPatch(string $message): never
{
    fwrite(STDERR, "\n[FAILED] {$message}\n");
    exit(1);
}

function projectFile(string $relative): string
{
    return __DIR__ . DIRECTORY_SEPARATOR
        . str_replace('/', DIRECTORY_SEPARATOR, $relative);
}

function readFileRequired(string $path): string
{
    if (!is_file($path)) {
        failPatch("File tidak ditemukan: {$path}");
    }

    $content = file_get_contents($path);

    if ($content === false) {
        failPatch("File tidak dapat dibaca: {$path}");
    }

    return str_replace(["\r\n", "\r"], "\n", $content);
}

function replaceOnce(
    string $content,
    string $needle,
    string $replacement,
    string $label
): string {
    $count = substr_count($content, $needle);

    if ($count !== 1) {
        failPatch(
            "Pola {$label} tidak cocok. Diharapkan 1, ditemukan {$count}. "
            . 'Tidak ada file project yang diubah.'
        );
    }

    return str_replace($needle, $replacement, $content);
}

function pregReplaceOnce(
    string $content,
    string $pattern,
    string $replacement,
    string $label
): string {
    $result = preg_replace($pattern, $replacement, $content, -1, $count);

    if ($result === null || $count !== 1) {
        failPatch(
            "Pola {$label} tidak cocok. Diharapkan 1, ditemukan {$count}. "
            . 'Tidak ada file project yang diubah.'
        );
    }

    return $result;
}

$root = __DIR__;
$payload = $root . DIRECTORY_SEPARATOR . 'payload';

if (!is_file($root . DIRECTORY_SEPARATOR . 'artisan')) {
    failPatch('Jalankan installer dari root project Laravel yang berisi file artisan.');
}

$modelRelative = 'app/Models/Hafalan.php';
$modelPath = projectFile($modelRelative);
$model = readFileRequired($modelPath);

if (!str_contains($model, 'use App\\Domain\\Hafalan\\HafalanScorePolicy;')) {
    $pattern = '~(use\s+App\\\\Traits\\\\LogsActivity;[^\n]*\n)~';
    $model = preg_replace_callback(
        $pattern,
        static fn(array $matches): string => $matches[1]
            . "use App\\Domain\\Hafalan\\HafalanScorePolicy;\n",
        $model,
        -1,
        $count
    );

    if ($model === null || $count !== 1) {
        failPatch(
            "Pola import HafalanScorePolicy tidak cocok. "
            . "Diharapkan 1, ditemukan {$count}. "
            . 'Tidak ada file project yang diubah.'
        );
    }
}

if (!str_contains($model, 'HafalanScorePolicy::normalizePayload')) {
    $replacement = <<<'PHPBLOCK'
$1

    protected static function booted(): void
    {
        static::saving(function (Hafalan $hafalan): void {
            $normalized = HafalanScorePolicy::normalizePayload([
                'status' => $hafalan->status,
                'nilai_label' => $hafalan->nilai_label,
            ]);

            $hafalan->nilai_label = $normalized['nilai_label'];
        });
    }
PHPBLOCK;

    $model = pregReplaceOnce(
        $model,
        '~(protected\s+\$casts\s*=\s*\[.*?\];)~s',
        $replacement,
        'blok casts model Hafalan'
    );
}

$copyFiles = [
    'app/Domain/Hafalan/HafalanScorePolicy.php',
    'app/Console/Commands/AuditHafalanScoreIntegrity.php',
    'tests/Unit/HafalanScorePolicyTest.php',
    'tests/Unit/StoreHafalanRequestRulesTest.php',
];

foreach ($copyFiles as $relative) {
    $source = $payload . DIRECTORY_SEPARATOR
        . str_replace('/', DIRECTORY_SEPARATOR, $relative);

    if (!is_file($source)) {
        failPatch("Payload tidak lengkap: {$relative}");
    }
}

/*
 * Stage 1 prerequisite check. Request harus sudah menerapkan aturan
 * nilai hanya untuk lulus sebelum guard dan test dipasang.
 */
$requestContent = readFileRequired(projectFile(
    'app/Http/Requests/StoreHafalanRequest.php'
));

if (
    !str_contains($requestContent, "\$status === 'lulus'")
    || !str_contains($requestContent, "if (\$status !== 'lulus')")
) {
    failPatch(
        'Hotfix tahap 1 belum terdeteksi pada StoreHafalanRequest.php. '
        . 'Terapkan paket nilai-ulang terlebih dahulu.'
    );
}

$timestamp = date('Ymd-His');
$backupRoot = $root . DIRECTORY_SEPARATOR . '.hotfix-backup'
    . DIRECTORY_SEPARATOR . PATCH_NAME . '-' . $timestamp;

$filesToBackup = array_merge([$modelRelative], $copyFiles);
$manifest = [];

foreach ($filesToBackup as $relative) {
    $target = projectFile($relative);
    $existed = is_file($target);
    $manifest[$relative] = ['existed' => $existed];

    if (!$existed) {
        continue;
    }

    $backup = $backupRoot . DIRECTORY_SEPARATOR
        . str_replace('/', DIRECTORY_SEPARATOR, $relative);
    $backupDir = dirname($backup);

    if (!is_dir($backupDir) && !mkdir($backupDir, 0775, true) && !is_dir($backupDir)) {
        failPatch("Gagal membuat direktori backup: {$backupDir}");
    }

    if (!copy($target, $backup)) {
        failPatch("Gagal membuat backup: {$relative}");
    }
}

if (!is_dir($backupRoot) && !mkdir($backupRoot, 0775, true) && !is_dir($backupRoot)) {
    failPatch("Gagal membuat direktori backup: {$backupRoot}");
}

file_put_contents(
    $backupRoot . DIRECTORY_SEPARATOR . 'manifest.json',
    json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
);

if (file_put_contents($modelPath, $model) === false) {
    failPatch("Gagal menulis {$modelRelative}");
}

foreach ($copyFiles as $relative) {
    $source = $payload . DIRECTORY_SEPARATOR
        . str_replace('/', DIRECTORY_SEPARATOR, $relative);
    $target = projectFile($relative);
    $targetDir = dirname($target);

    if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
        failPatch("Gagal membuat direktori: {$targetDir}");
    }

    if (!copy($source, $target)) {
        failPatch("Gagal menyalin: {$relative}");
    }
}

file_put_contents(
    $root . DIRECTORY_SEPARATOR . '.score-guard-last-backup',
    $backupRoot
);

fwrite(STDOUT, "\n[SUCCESS] Score guard berhasil diterapkan.\n");
fwrite(STDOUT, "Backup: {$backupRoot}\n\n");
fwrite(STDOUT, "Jalankan:\n");
fwrite(STDOUT, "  php artisan optimize:clear\n");
fwrite(STDOUT, "  php artisan test --filter=HafalanScore\n");
fwrite(STDOUT, "  php artisan test --filter=StoreHafalanRequestRulesTest\n");
fwrite(STDOUT, "  php artisan hafalan:audit-score-integrity\n");
