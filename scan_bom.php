<?php
// scan_bom.php

$directory = new RecursiveDirectoryIterator('public');
$iterator = new RecursiveIteratorIterator($directory);
$count = 0;

foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $path = $file->getRealPath();
        // Lit les 3 premiers octets du fichier
        if (file_get_contents($path, false, null, 0, 3) === "\xEF\xBB\xBF") {
            echo "⚠️ BOM trouvé dans : " . $path . PHP_EOL;
            $count++;
        }
    }
}

if ($count === 0) {
    echo "✅ Scan terminé : Aucun caractère BOM détecté dans le dossier public/ !" . PHP_EOL;
} else {
    echo "❌ Scan terminé : $count fichier(s) à corriger." . PHP_EOL;
}
