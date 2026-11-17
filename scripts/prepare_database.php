<?php
// Minimal installer helper: never echoes credentials, never deletes an existing database.
$path = dirname(__DIR__).'/.env';
if (!is_file($path)) { fwrite(STDERR, "[ERRO] .env ausente\n"); exit(1); }
$vars = [];
foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    $line = trim($line);
    if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) continue;
    [$key, $val] = explode('=', $line, 2);
    $key = trim($key); $val = trim($val);
    if (strlen($val) >= 2 && ($val[0] === '"' || $val[0] === "'")) {
        if (substr($val, -1) === $val[0]) $val = substr($val, 1, -1);
    }
    $vars[$key] = $val;
}
$host = $vars['DB_HOST'] ?? '127.0.0.1';
$port = $vars['DB_PORT'] ?? '3306';
$db = $vars['DB_DATABASE'] ?? 'hospital_it_noc';
$user = $vars['DB_USERNAME'] ?? 'root';
$password = $vars['DB_PASSWORD'] ?? '';
if (($vars['DB_CONNECTION'] ?? 'mysql') !== 'mysql') { fwrite(STDERR, "[ERRO] DB_CONNECTION deve ser mysql\n"); exit(1); }
if (!preg_match('/^[A-Za-z0-9_]+$/D', $db) || !ctype_digit((string)$port)) { fwrite(STDERR, "[ERRO] Nome do banco/porta invalido\n"); exit(1); }
try {
    if (!extension_loaded('pdo_mysql')) throw new RuntimeException('Extensao pdo_mysql desativada');
    $pdo = new PDO("mysql:host={$host};port={$port};charset=utf8mb4", $user, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 8]);
    if (!in_array('--test', $argv, true)) {
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$db}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "Banco {$db} confirmado/criado.\n";
    }
    $pdo->exec("USE `{$db}`");
    echo "Conexao MySQL OK.\n";
} catch (Throwable $e) {
    fwrite(STDERR, "[ERRO] Conexao MySQL ou criacao do banco falhou: ".$e->getMessage()."\nVerifique no .env DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME e DB_PASSWORD; inicie o MySQL no ServBay.\n");
    exit(1);
}
