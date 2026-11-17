<?php
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) require $maintenance;
$autoload = __DIR__.'/../vendor/autoload.php';
if (!is_file($autoload)) {
    http_response_code(503);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html lang="pt-BR"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<title>Instalacao pendente - Hospital IT NOC</title>';
    echo '<body style="background:#0e1420;color:#e9eff8;font:16px/1.7 system-ui,sans-serif;max-width:660px;margin:12vh auto;padding:20px">';
    echo '<h1>Instalacao do Laravel pendente</h1><p>As dependencias PHP ainda nao foram instaladas.</p>';
    echo '<p>Na pasta <strong>hospital-it-command-center</strong>, execute <code>INICIAR_WINDOWS.bat</code>.';
    echo ' O instalador mostra os erros e grava <code>INSTALACAO_SERVBAY.log</code>.</p>';
    echo '<p>Depois que o Composer concluir a instalacao, atualize esta pagina.</p></body></html>';
    exit;
}
require $autoload;
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->handleRequest(Request::capture());
