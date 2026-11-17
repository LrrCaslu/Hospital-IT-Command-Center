$ErrorActionPreference = 'Continue'
. (Join-Path $PSScriptRoot 'common.ps1')
$script:LogPath = Join-Path $ProjectRoot 'VERIFICACAO_AMBIENTE.log'
[System.IO.File]::WriteAllText($script:LogPath, 'DIAGNOSTICO HOSPITAL NOC - ' + (Get-Date).ToString('s') + [Environment]::NewLine)
Log-Line "Diretorio: $ProjectRoot"
foreach ($name in @('php','php-8.3','php-8.4','composer','mysql','npm')) {
    $cmd = Get-Command $name -ErrorAction SilentlyContinue | Select-Object -First 1
    if ($cmd) { Log-Line "$name : $($cmd.Source)" } else { Log-Line "$name : NAO ENCONTRADO" }
}
try {
    Initialize-LaravelDirectories
    $php = Choose-PHP
    Log-Line "PHP selecionado: $php"
    Run-Logged $php @('-v') 'php -v'
    Run-Logged $php @('-m') 'extensoes PHP'
    if (Test-Path (Join-Path $ProjectRoot '.env')) {
        Run-Logged $php @((Join-Path $PSScriptRoot 'prepare_database.php'),'--test') 'testar MySQL (somente leitura)'
    } else { Log-Line '.env NAO EXISTE' }
    if (Test-Path (Join-Path $ProjectRoot 'vendor/autoload.php')) {
        Log-Line 'vendor/autoload.php EXISTE.'
        Run-Logged $php @('artisan','--version') 'versao Laravel'
    } else {
        Log-Line 'vendor/autoload.php AUSENTE. O Laravel ainda nao pode iniciar.'
    }
} catch { Log-Line "ERRO: $($_.Exception.Message)" }
Log-Line "Diagnostico concluido: $script:LogPath"
Write-Host 'Janela mantida aberta para inspecao.'
