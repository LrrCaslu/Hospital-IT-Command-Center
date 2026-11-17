<?php
/**
 * Prints READY when seed data exists or NEEDED when it should be created.
 * Does not reveal credentials, alter tables or print any private record.
 */
$envFile = dirname(__DIR__) . '/.env';
if (!is_file($envFile)) { fwrite(STDERR, "[ERRO] .env nao encontrado\n"); exit(1); }
$vars = [];
foreach (file($envFile, FILE_IGNORE_NEW_LINES) as $line) {
    $line = trim($line);
    if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) continue;
    [$key, $value] = explode('=', $line, 2);
    $value = trim($value);
    if (strlen($value) > 1 && in_array($value[0], ['"', "'"], true) && substr($value, -1) === $value[0]) {
        $value = substr($value, 1, -1);
    }
    $vars[trim($key)] = $value;
}
if (($vars['DB_CONNECTION'] ?? 'mysql') !== 'mysql') { fwrite(STDERR, "[ERRO] DB_CONNECTION precisa ser mysql\n"); exit(1); }
$db = $vars['DB_DATABASE'] ?? 'hospital_it_noc';
$port = $vars['DB_PORT'] ?? '3306';
if (!preg_match('/^[A-Za-z0-9_]+$/D', $db) || !ctype_digit((string)$port)) { fwrite(STDERR, "[ERRO] DB_DATABASE ou DB_PORT invalidos\n"); exit(1); }
try {
    $pdo = new PDO(
        'mysql:host=' . ($vars['DB_HOST'] ?? '127.0.0.1') . ';port=' . $port . ';dbname=' . $db . ';charset=utf8mb4',
        $vars['DB_USERNAME'] ?? 'root', $vars['DB_PASSWORD'] ?? '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 8]
    );
    $units = (int)$pdo->query('SELECT COUNT(*) FROM organizational_units')->fetchColumn();
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
    $stmt->execute(['admin@hospital.local']);
    $admin = (int)$stmt->fetchColumn();
    echo $units >= 6 && $admin > 0 ? "READY\n" : "NEEDED\n";
} catch (PDOException $e) {
    // Missing tables mean seeding cannot be evaluated: let db:seed fail transparently.
    fwrite(STDERR, '[ERRO] Nao foi possivel verificar dados demonstrativos: ' . $e->getMessage() . "\n");
    exit(1);
}
