<?php
// TEMPORARY DIAGNOSTIC - DELETE THIS FILE AFTER USE
if (PHP_SAPI !== 'cli' && ($_GET['k'] ?? '') !== 'lab6') { http_response_code(404); exit; }
if (PHP_SAPI !== 'cli') header('Content-Type: text/plain');

$host = getenv('DB_HOST'); $port = getenv('DB_PORT'); $db = getenv('DB_NAME');
$user = getenv('DB_USER'); $pass = getenv('DB_PASSWORD');
$ca = __DIR__ . '/../app/certs/ca.pem';

echo "PHP " . PHP_VERSION . " | " . OPENSSL_VERSION_TEXT . "\n";
echo "env: host=" . ($host ? 'set' : 'MISSING') . " port=" . ($port ?: 'MISSING') . " db=" . ($db ?: 'MISSING')
   . " user=" . ($user ? 'set' : 'MISSING') . " pass=" . ($pass ? 'set' : 'MISSING') . "\n";
echo "DB_SSL_VERIFY env = " . var_export(getenv('DB_SSL_VERIFY'), true) . "\n";
echo "ca.pem exists: " . (file_exists($ca) ? 'yes, ' . filesize($ca) . ' bytes' : 'NO') . "\n";
if (file_exists($ca)) {
    $c = file_get_contents($ca);
    echo "ca.pem has BEGIN CERTIFICATE line: " . (strpos($c, '-----BEGIN CERTIFICATE-----') !== false ? 'yes' : 'NO') . "\n";
}
echo "\n";

$dsn  = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
$base = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 10];
$tests = [
    'A: CA file, verify OFF'        => [1009 => $ca, 1014 => false],
    'B: CA file, verify ON'         => [1009 => $ca, 1014 => true],
    'C: SSL, no CA file, no verify' => [1011 => 'DEFAULT', 1014 => false],
    'D: no SSL options at all'      => [],
];
foreach ($tests as $label => $opts) {
    $warn = null;
    set_error_handler(function ($n, $s) use (&$warn) { $warn = $s; return true; });
    try {
        $pdo = new PDO($dsn, $user, $pass, $base + $opts);
        $r = $pdo->query("SHOW STATUS LIKE 'Ssl_cipher'")->fetch(PDO::FETCH_NUM);
        echo "$label => CONNECTED (cipher: " . ($r[1] ?: 'none') . ")\n";
    } catch (Throwable $e) {
        echo "$label => FAILED: " . $e->getMessage() . ($warn ? "\n      PHP said: $warn" : '') . "\n";
    }
    restore_error_handler();
}