param([ValidateSet('full', 'database')][string]$Mode = 'full')
$ErrorActionPreference = 'Stop'
. (Join-Path $PSScriptRoot 'common.ps1')
$script:LogPath = Join-Path $ProjectRoot $(if ($Mode -eq 'database') { 'PREPARACAO_BANCO.log' } else { 'INSTALACAO_SERVBAY.log' })
[System.IO.File]::WriteAllText($script:LogPath, 'HOSPITAL IT NOC - INSTALACAO - ' + (Get-Date).ToString('s') + [Environment]::NewLine)
Write-Host '================================================================'
Write-Host ' HOSPITAL IT COMMAND CENTER - PREPARACAO LOCAL / SERVBAY'
Write-Host ' Nenhum site ou dominio sera alterado.'
Write-Host '================================================================'
try {
    Log-Line "Pasta do projeto: $ProjectRoot"
    if (-not (Test-Path (Join-Path $ProjectRoot 'artisan'))) { throw 'Pasta invalida: artisan nao encontrado.' }
    if (-not (Test-Path (Join-Path $ProjectRoot 'composer.json'))) { throw 'composer.json nao encontrado.' }
    # FIX: Laravel requires bootstrap/cache before composer post-autoload scripts.
    Initialize-LaravelDirectories
    $php = Choose-PHP
    $phpVersion = (& $php -r 'echo PHP_VERSION;' 2>$null) -join ''
    Log-Line "PHP escolhido: $php (v$phpVersion)"
    $where = (where.exe php 2>$null) -join '; '
    Log-Line "Executaveis php no PATH: $where"

    if (-not (Test-Path (Join-Path $ProjectRoot '.env'))) {
        Copy-Item -LiteralPath (Join-Path $ProjectRoot '.env.example') -Destination (Join-Path $ProjectRoot '.env')
        Log-Line '.env criado. Ajuste credenciais do MySQL se necessario.'
    } else { Log-Line '.env existente preservado (APP_KEY e credenciais nao serao substituidas).' }

    if (-not (Test-Path (Join-Path $ProjectRoot 'vendor/autoload.php')) -and $Mode -eq 'database') {
        throw 'Dependencias Laravel ausentes. Rode INICIAR_WINDOWS.bat primeiro.'
    }
    if (-not (Test-Path (Join-Path $ProjectRoot 'vendor/autoload.php'))) {
        Log-Line 'vendor/autoload.php ausente: instalando dependencias PHP.'
        $composer = Get-Command composer -ErrorAction SilentlyContinue | Select-Object -First 1
        if ($composer) {
            Log-Line "Composer encontrado: $($composer.Source)"
            $composerPhar = Join-Path (Split-Path -Parent $composer.Source) 'composer.phar'
            if (Test-Path -LiteralPath $composerPhar) {
                Run-Logged $php @($composerPhar, 'install', '--no-interaction', '--prefer-dist', '--no-progress') 'composer install'
            } else {
                Run-Logged $composer.Source @('install', '--no-interaction', '--prefer-dist', '--no-progress') 'composer install'
            }
        } else {
            Log-Line 'Composer nao localizado. Instalando Composer oficial com assinatura SHA-384 validada.'
            $setup = Join-Path $PSScriptRoot 'composer-setup.php'
            try {
                $sig = (Invoke-WebRequest -UseBasicParsing -Uri 'https://composer.github.io/installer.sig' -TimeoutSec 30).Content.Trim().ToLowerInvariant()
                Invoke-WebRequest -UseBasicParsing -Uri 'https://getcomposer.org/installer' -OutFile $setup -TimeoutSec 60
                $hash = (Get-FileHash -Path $setup -Algorithm SHA384).Hash.ToLowerInvariant()
                if ($sig -notmatch '^[0-9a-f]{96}$' -or $sig -ne $hash) { throw 'Assinatura Composer invalida.' }
                Run-Logged $php @($setup, "--install-dir=$PSScriptRoot", '--filename=composer.phar') 'instalar Composer local'
            } finally { if (Test-Path $setup) { Remove-Item $setup -Force } }
            $phar = Join-Path $PSScriptRoot 'composer.phar'
            if (-not (Test-Path $phar)) { throw 'composer.phar nao gerado.' }
            Run-Logged $php @($phar, 'install', '--no-interaction', '--prefer-dist', '--no-progress') 'composer install (local)'
        }
    } else { Log-Line 'vendor/autoload.php presente. Reutilizando dependencias ja instaladas.' }

    if (-not (Test-Path (Join-Path $ProjectRoot 'vendor/autoload.php'))) { throw 'Composer nao gerou vendor/autoload.php.' }
    Run-Logged $php @('artisan', '--version') 'verificar inicializacao do Laravel'
    Run-Logged $php @('artisan', 'package:discover', '--ansi') 'registrar provedores dos pacotes'
    if (-not (Get-AppKeyValue)) {
        Run-Logged $php @('artisan', 'key:generate', '--force') 'gerar APP_KEY'
    } else { Log-Line 'APP_KEY existente preservada.' }
    if (-not (Get-AppKeyValue)) { throw 'APP_KEY continua vazia apos key:generate. Nao e possivel continuar.' }
    Log-Line 'OK: APP_KEY confirmada (valor nao exibido).'

    Run-Logged $php @((Join-Path $PSScriptRoot 'prepare_database.php')) 'conectar MySQL e criar banco se necessario'
    Run-Logged $php @('artisan', 'migrate', '--force') 'executar migrations (sem apagar dados)'
    # Re-seeding on every run resets demo accounts. Only seed if necessary.
    $seedState = (& $php (Join-Path $PSScriptRoot 'seed_status.php') 2>&1 | Out-String).Trim()
    if ($LASTEXITCODE -ne 0 -or $seedState -notin @('READY', 'NEEDED')) {
        throw "Falha ao verificar estado dos seeders: $seedState"
    }
    if ($seedState -eq 'NEEDED') {
        Log-Line 'Dados demonstrativos incompletos: executando seeders.'
        Run-Logged $php @('artisan', 'db:seed', '--force') 'criar dados demonstrativos'
    } else { Log-Line 'Dados demonstrativos existentes: seeders ignorados para preservar cadastros.' }

    if ($Mode -eq 'full') {
        try { Run-Logged $php @('artisan', 'storage:link') 'criar link de armazenamento' }
        catch { Log-Line ('AVISO: storage:link nao foi concluido: ' + $_.Exception.Message) }
    }
    Run-Logged $php @('artisan', 'optimize:clear') 'limpar caches'
    Run-Logged $php @((Join-Path $PSScriptRoot 'check_database.php')) 'verificar tabelas, seis unidades e conta administradora'
    Log-Line 'SUCESSO: Laravel, banco, tabelas e dados demonstrativos verificados.'
    Write-Host ''
    Write-Host 'BANCO DE DADOS PRONTO! Nenhuma configuracao de site foi alterada.' -ForegroundColor Green
    Write-Host ('Pasta PUBLIC no ServBay: ' + (Join-Path $ProjectRoot 'public'))
    Write-Host 'Login DEMO: admin@hospital.local / Admin@123456'
    if ($Mode -eq 'full') { Write-Host 'Para simular eventos: INICIAR_COLETOR_WINDOWS.bat' }
    exit 0
} catch {
    Log-Line ('ERRO: ' + $_.Exception.Message)
    Write-Host ''
    Write-Host 'FALHA: preparacao nao concluida. Consulte o LOG na pasta do projeto.' -ForegroundColor Red
    Write-Host 'O instalador NAO apagou nem recriou as tabelas existentes.'
    exit 1
}
