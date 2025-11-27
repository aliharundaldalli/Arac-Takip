<?php
/**
 * Proje klasör yapısını (tree) text/markdown formatında yazdırır.
 * Bunu bir kez çalıştır, çıktıyı README.md içine kopyala.
 */

declare(strict_types=1);

// ----------------------------------------------------
// AYARLAR
// ----------------------------------------------------

// Proje kök klasörü (bu dosyanın bir üst klasörü olsun istiyorsan:)
$rootDir = realpath(__DIR__ . '/..');   // bu dosya /tools içindeyse
// $rootDir = __DIR__;                  // dosya kökteyse bunu kullan

// README'de görünsün istediğin isim:
$rootLabel = basename($rootDir);

// Hariç tutulacak klasör ve dosyalar
$ignoreList = [
    '.', '..',
    '.git', '.idea', '.vscode',
    'vendor', 'node_modules',
    'storage', 'cache', 'logs',
    '.DS_Store', 'Thumbs.db'
];

// ----------------------------------------------------
// FONKSİYONLAR
// ----------------------------------------------------

/**
 * Verilen klasördeki elemanları filtreleyip sıralar.
 * Önce klasörler, sonra dosyalar; ikisi de alfabetik.
 */
function getDirectoryItems(string $dir, array $ignoreList): array
{
    $items = array_diff(scandir($dir), $ignoreList);
    $result = [];

    foreach ($items as $item) {
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        $result[] = [
            'name' => $item,
            'path' => $path,
            'is_dir' => is_dir($path),
        ];
    }

    // Önce klasörler sonra dosyalar, isim olarak da A-Z
    usort($result, function ($a, $b) {
        if ($a['is_dir'] === $b['is_dir']) {
            return strcasecmp($a['name'], $b['name']);
        }
        return $a['is_dir'] ? -1 : 1;
    });

    return $result;
}

/**
 * Ağaç çıktısını (├──, └──, │) ile birlikte yazdırır.
 */
function printTree(string $dir, array $ignoreList, string $prefix = ''): void
{
    $items = getDirectoryItems($dir, $ignoreList);
    $total = count($items);
    $index = 0;

    foreach ($items as $item) {
        $index++;
        $isLast = ($index === $total);

        $connector = $isLast ? '└── ' : '├── ';
        echo $prefix . $connector . $item['name'] . PHP_EOL;

        if ($item['is_dir']) {
            $childPrefix = $prefix . ($isLast ? '    ' : '│   ');
            printTree($item['path'], $ignoreList, $childPrefix);
        }
    }
}

// ----------------------------------------------------
// ÇIKTI
// ----------------------------------------------------

header('Content-Type: text/plain; charset=utf-8');

// Markdown içinde ``` bloğuna uygun olsun diye:
echo $rootLabel . '/' . PHP_EOL;
printTree($rootDir, $ignoreList);

