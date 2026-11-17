<?php
/** Diagnostico do banco: somente leitura. Nunca imprime a senha do .env. */
$envFile = dirname(__DIR__).'/.env';
if (!is_file($envFile)) { fwrite(STDERR, "[ERRO] Arquivo .env nao encontrado.\n"); exit(1); }
$vars = [];
foreach (file($envFile, FILE_IGNORE_NEW_LINES) as $line) {
    $line = trim($line);
    if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) continue;
    [$key, $value] = explode('=', $line, 2);
    $key = trim($key); $value = trim($value);
    if (strlen($value) >= 2 && in_array($value[0], ['"', "'"], true) && substr($value, -1) === $value[0]) {
        $value = substr($value, 1, -1);
    }
    $vars[$key] = $value;
}
if (($vars['DB_CONNECTION'] ?? 'mysql') !== 'mysql') { fwrite(STDERR, "[ERRO] DB_CONNECTION precisa ser mysql.\n"); exit(1); }
$host = $vars['DB_HOST'] ?? '127.0.0.1';
$port = $vars['DB_PORT'] ?? '3306';
$db = $vars['DB_DATABASE'] ?? 'hospital_it_noc';
if (!preg_match('/^[A-Za-z0-9_]+$/D', $db) || !ctype_digit((string) $port)) { fwrite(STDERR, "[ERRO] Nome do banco/porta invalido.\n"); exit(1); }
if (!extension_loaded('pdo_mysql')) { fwrite(STDERR, "[ERRO] Extensao pdo_mysql ausente.\n"); exit(1); }
if (empty($vars['APP_KEY'])) { fwrite(STDERR, "[ERRO] APP_KEY nao gerada.\n"); exit(1); }
try {
    $pdo = new PDO("mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4", $vars['DB_USERNAME'] ?? 'root', $vars['DB_PASSWORD'] ?? '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 8]);
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = ? AND table_type = ?');
    $stmt->execute([$db, 'BASE TABLE']);
    $tables = (int) $stmt->fetchColumn();
    echo "Banco conectado: {$db}\nTabelas: {$tables}\n";
    $required = ['users', 'organizational_units', 'assets', 'incidents', 'alerts', 'integrations', 'migrations'];
    foreach ($required as $table) {
        $exists = $pdo->query("SHOW TABLES LIKE '".$table."'")->fetchColumn();
        if (!$exists) { throw new RuntimeException("Tabela necessaria ausente: {$table}. Rode as migrations."); }
    }
    $units = (int)$pdo->query('SELECT COUNT(*) FROM organizational_units')->fetchColumn();
    $users = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    $adminQuery = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
    $adminQuery->execute(['admin@hospital.local']);
    $admin = (int)$adminQuery->fetchColumn();
    echo "Unidades cadastradas: {$units}\nUsuarios cadastrados: {$users}\nConta demonstrativa admin: ".($admin ? 'SIM' : 'NAO')."\n";
    if ($units < 6 || $admin === 0) { throw new RuntimeException('Dados demonstrativos incompletos. Execute php artisan db:seed --force.'); }
    if (!is_file(dirname(__DIR__).'/vendor/autoload.php')) { throw new RuntimeException('vendor/autoload.php nao encontrado. Execute composer install.'); }
    echo "[OK] Banco, migrations e seeders disponiveis.\n";
} catch (Throwable $e) {
    fwrite(STDERR, "[ERRO] Falha na verificacao do banco: ".$e->getMessage()."\n");
    exit(1);
}
