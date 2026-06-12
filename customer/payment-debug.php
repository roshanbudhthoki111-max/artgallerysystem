<?php
// TEMPORARY DEBUG PAGE — delete this file after fixing
require_once __DIR__ . '/../includes/functions.php';
requireRole('customer', '/art-gallery/login.php');

$qrDir = __DIR__ . '/../uploads/qr/';

echo "<pre style='font-family:monospace;padding:2rem;'>";
echo "=== QR PATH DEBUG ===\n\n";
echo "__DIR__         : " . __DIR__ . "\n";
echo "QR folder path  : " . $qrDir . "\n";
echo "QR folder exists: " . (is_dir($qrDir) ? "YES" : "NO — folder missing!") . "\n\n";

echo "=== FILES IN uploads/qr/ ===\n";
if (is_dir($qrDir)) {
    $files = scandir($qrDir);
    foreach ($files as $f) {
        if ($f === '.' || $f === '..') continue;
        echo "  " . $f . " (" . filesize($qrDir.$f) . " bytes)\n";
    }
    if (count($files) <= 2) echo "  (empty — no files found)\n";
} else {
    echo "  Folder does not exist!\n";
}

echo "\n=== FILES IN uploads/ ===\n";
$uploadsDir = __DIR__ . '/../uploads/';
if (is_dir($uploadsDir)) {
    $files = scandir($uploadsDir);
    foreach ($files as $f) {
        if ($f === '.' || $f === '..') continue;
        echo "  " . $f . "\n";
    }
}

echo "\n=== CHECKING SPECIFIC FILES ===\n";
$checks = [
    'uploads/qr/esewa_personal.png',
    'uploads/qr/khalti_personal.png',
    'uploads/qr/esewa_personal.jpg',
    'uploads/qr/khalti_personal.jpg',
    'uploads/esewa_personal.png',
    'uploads/khalti_personal.png',
];
foreach ($checks as $c) {
    $full = __DIR__ . '/../' . $c;
    echo "  $c : " . (file_exists($full) ? "FOUND ✓" : "not found") . "\n";
}
echo "</pre>";
