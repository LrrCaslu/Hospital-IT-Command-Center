param([ValidateSet('collector','queue')][string]$Mode = 'collector')
$ErrorActionPreference = 'Stop'
. (Join-Path $PSScriptRoot 'common.ps1')
$script:LogPath = Join-Path $ProjectRoot ("EXECUCAO_" + $Mode.ToUpperInvariant() + '.log')
[System.IO.File]::WriteAllText($script:LogPath, 'HOSPITAL NOC - ' + $Mode + [Environment]::NewLine)
try {
    Initialize-LaravelDirectories
    if (-not (Test-Path (Join-Path $ProjectRoot 'vendor/autoload.php'))) {
        throw 'Laravel nao instalado: execute INICIAR_WINDOWS.bat primeiro.'
    }
    $php = Choose-PHP
    Log-Line "PHP: $php"
    if ($Mode -eq 'collector') {
        Run-Logged $php @('artisan','noc:collect','--sleep=10') 'coletor NOC (Ctrl+C para parar)'
    } else {
        Run-Logged $php @('artisan','queue:work','database','--sleep=2','--tries=2','--timeout=55') 'worker de filas (Ctrl+C para parar)'
    }
} catch { Log-Line ("ERRO: " + $_.Exception.Message) }
Write-Host 'Terminal mantido aberto. Confira o LOG acima.'
