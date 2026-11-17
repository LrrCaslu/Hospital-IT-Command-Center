$ErrorActionPreference = 'Stop'
. (Join-Path $PSScriptRoot 'common.ps1')
$script:LogPath = Join-Path $ProjectRoot 'VERIFICACAO_BANCO.log'
[System.IO.File]::WriteAllText($script:LogPath, 'VERIFICACAO DO BANCO - ' + (Get-Date).ToString('s') + [Environment]::NewLine)
try {
    Initialize-LaravelDirectories
    $php = Choose-PHP
    Log-Line "PHP CLI: $php"
    Run-Logged $php @((Join-Path $PSScriptRoot 'check_database.php')) 'verificar banco, tabelas e seeders (somente leitura)'
    Log-Line '[OK] Banco preparado. Configuracao e abertura do site ficam a cargo do ServBay.'
    Log-Line ('Pasta publica: ' + (Join-Path $ProjectRoot 'public'))
} catch {
    Log-Line ('[ERRO] ' + $_.Exception.Message)
    Write-Host 'Verifique o arquivo .env, Composer e o MySQL do ServBay.' -ForegroundColor Red
    exit 1
}
Write-Host 'A janela permanece aberta para consulta.'

exit 0
