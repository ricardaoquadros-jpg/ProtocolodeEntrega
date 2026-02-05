<?php
$file = __DIR__ . '/logs_php_errors.log';
if (!file_exists($file)) {
    echo "Log file not found.\n";
    exit;
}

$lines = file($file);
$last20 = array_slice($lines, -20);
foreach ($last20 as $line) {
    // Attempt to detect encoding or just print. 
    // If it's UTF-16, we might need iconv.
    // Let's try to detect if it's UTF-16LE (common in Windows PowerShell/cmd redirects)
    $content = $line;
    // Remove null bytes which are common in wide char encodings
    $clean = str_replace("\0", "", $content);
    echo trim($clean) . "\n";
}
?>
