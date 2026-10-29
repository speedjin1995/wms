<?php
// Copy to secret.php (git-ignored) on each server. wms_admin reads the same file.
// Keys are 32 random bytes, base64. Generate: php -r "echo base64_encode(random_bytes(32));"
// To rotate: add the next version, point CURRENT at it, keep the old one until every secret is re-saved.
define('WMS_SECRET_KEYS', [
    1 => base64_decode('REPLACE_WITH_BASE64_KEY')
]);
define('WMS_SECRET_KEY_CURRENT', 1);
