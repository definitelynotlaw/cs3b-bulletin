<?php
require __DIR__ . '/../functions.php';

$allowed = ['image/jpeg', 'image/png', 'image/webp'];
$expectedExtensions = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
];
$filenames = [];

foreach ($expectedExtensions as $mime => $extension) {
    if (upload_extension_for_mime($mime, $allowed) !== $extension) {
        throw new RuntimeException('Unexpected extension for ' . $mime);
    }

    $filename = safe_upload_filename($mime, $allowed);
    if (!is_string($filename) || !preg_match('/^[a-f0-9]{32}\.' . $extension . '$/', $filename)) {
        throw new RuntimeException('Unsafe generated filename for ' . $mime);
    }
    $filenames[] = $filename;
}

if (count(array_unique($filenames)) !== count($filenames)) {
    throw new RuntimeException('Generated filenames were not unique.');
}
if (upload_extension_for_mime('application/x-php', $allowed) !== null) {
    throw new RuntimeException('Unsupported MIME type was accepted.');
}
if (safe_upload_filename('image/jpeg', ['image/png']) !== null) {
    throw new RuntimeException('MIME type outside the call-site allowlist was accepted.');
}

echo "Upload security checks passed.\n";