<?php

declare(strict_types=1);

$rootPath = realpath(__DIR__ . '/..');
if ($rootPath === false) {
    fwrite(STDERR, "Não foi possível determinar o diretório raiz.\n");
    exit(1);
}

$releaseDir = $rootPath . '/storage/releases';
if (!is_dir($releaseDir) && !mkdir($releaseDir, 0775, true) && !is_dir($releaseDir)) {
    fwrite(STDERR, "Não foi possível criar o diretório de releases em {$releaseDir}.\n");
    exit(1);
}

$timestamp = date('Ymd-His');
$zipName = "bsoutvendas-{$timestamp}.zip";
$zipPath = $releaseDir . '/' . $zipName;

$zip = new ZipArchive();
if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    fwrite(STDERR, "Falha ao abrir o arquivo {$zipPath} para escrita.\n");
    exit(1);
}

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($rootPath, FilesystemIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

$excludes = array_filter([
    realpath($rootPath . '/storage/releases'),
    realpath($rootPath . '/storage/logs'),
    realpath($rootPath . '/.git'),
    realpath($rootPath . '/vendor'),
]);

foreach ($iterator as $item) {
    $itemPath = $item->getPathname();

    foreach ($excludes as $exclude) {
        if ($exclude !== false && strpos($itemPath, $exclude) === 0) {
            continue 2;
        }
    }

    $relativePath = substr($itemPath, strlen($rootPath) + 1);

    if ($item->isDir()) {
        $zip->addEmptyDir($relativePath);
        continue;
    }

    if (!$zip->addFile($itemPath, $relativePath)) {
        fwrite(STDERR, "Aviso: não foi possível adicionar {$relativePath} ao pacote.\n");
    }
}

$zip->close();

echo "Pacote criado em: {$zipPath}\n";
