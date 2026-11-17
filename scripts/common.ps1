# Shared functions for Windows PowerShell 5.1 and ServBay.
# ASCII source to avoid Windows console encoding problems.
$ProjectRoot = (Resolve-Path -LiteralPath (Join-Path $PSScriptRoot '..')).Path
Set-Location -LiteralPath $ProjectRoot

function Choose-PHP {
    $candidates = @('php-8.4', 'php-8.3', 'php-8.5', 'php')
    foreach ($candidate in $candidates) {
        $cmd = Get-Command $candidate -ErrorAction SilentlyContinue | Select-Object -First 1
        if (-not $cmd) { continue }
        try {
            $v = (& $cmd.Source -r 'echo PHP_VERSION_ID;' 2>$null) -join ''
            if (-not ($v -match '^\d+$') -or [int]$v -lt 80300) { continue }
            $mods = @(& $cmd.Source -m 2>$null)
            $needed = @('PDO', 'pdo_mysql', 'mbstring', 'openssl', 'fileinfo', 'xml', 'ctype', 'tokenizer')
            $missing = @($needed | Where-Object { $mods -notcontains $_ })
            if ($missing.Count -eq 0) { return $cmd.Source }
            Write-Host ("[AVISO] {0} sem extensoes: {1}" -f $cmd.Source, ($missing -join ', ')) -ForegroundColor Yellow
        } catch { }
    }
    throw 'PHP 8.3+ com pdo_mysql, mbstring, openssl, fileinfo, xml, ctype e tokenizer nao encontrado. Ative as extensoes no ServBay.'
}

function Log-Line([string]$msg) {
    $stamp = Get-Date -Format 'yyyy-MM-dd HH:mm:ss'
    $line = "[$stamp] $msg"
    Write-Host $line
    [System.IO.File]::AppendAllText($script:LogPath, $line + [Environment]::NewLine, [System.Text.Encoding]::UTF8)
}

function Initialize-LaravelDirectories {
    # These directories have to exist before Composer triggers artisan package:discover.
    $required = @(
        'bootstrap/cache',
        'storage/logs',
        'storage/app',
        'storage/app/public',
        'storage/framework',
        'storage/framework/cache',
        'storage/framework/cache/data',
        'storage/framework/sessions',
        'storage/framework/views',
        'storage/framework/testing'
    )
    foreach ($relative in $required) {
        $path = Join-Path $ProjectRoot ($relative -replace '/', [System.IO.Path]::DirectorySeparatorChar)
        try {
            if (-not (Test-Path -LiteralPath $path -PathType Container)) {
                New-Item -ItemType Directory -Path $path -Force -ErrorAction Stop | Out-Null
            }
            # Test an actual write, instead of guessing based on ACLs.
            $probe = Join-Path $path ('.noc-write-test-' + [guid]::NewGuid().ToString('N'))
            try { [System.IO.File]::WriteAllText($probe, 'ok', [System.Text.Encoding]::ASCII) }
            finally { if (Test-Path -LiteralPath $probe) { Remove-Item -LiteralPath $probe -Force -ErrorAction Stop } }
        } catch {
            throw "Pasta obrigatoria nao gravavel: $path. Verifique permissoes do usuario Windows. Detalhes: $($_.Exception.Message)"
        }
    }
    Log-Line 'OK: pastas do Laravel criadas e com gravacao confirmada (bootstrap/cache e storage).'
}

function Run-Logged([string]$executable, [string[]]$arguments, [string]$label) {
    Log-Line "INICIANDO: $label"
    $oldPreference = $ErrorActionPreference
    $ErrorActionPreference = 'Continue'
    $script:CommandHadFatalOutput = $false
    $exitCode = $null
    try {
        & $executable @arguments 2>&1 | ForEach-Object {
            $line = $_.ToString()
            Write-Host $line
            [System.IO.File]::AppendAllText($script:LogPath, $line + [Environment]::NewLine, [System.Text.Encoding]::UTF8)
            # Some ServBay .cmd wrappers swallow PHP's exit status. Inspect fatal output too.
            if ($line -match '(?i)^\s*(?:In\s+.+\.php\s+line\s+\d+\s*:|Fatal error\s*:|Parse error\s*:|Uncaught\s+|\[ERRO\])' -or
                $line -match '(?i)directory must be present and writable' -or
                $line -match '(?i)^\s*Script\s+@php\s+artisan\s+.+\s+returned\s+with\s+error') {
                $script:CommandHadFatalOutput = $true
            }
        }
        $exitCode = $LASTEXITCODE
    } finally { $ErrorActionPreference = $oldPreference }
    if ($exitCode -ne 0 -or $script:CommandHadFatalOutput) {
        $detail = if ($script:CommandHadFatalOutput) { 'Saida de erro fatal detectada' } else { "codigo de saida $exitCode" }
        throw "$label falhou ($detail). Consulte $script:LogPath."
    }
    Log-Line "OK: $label"
}

function Get-AppKeyValue {
    $file = Join-Path $ProjectRoot '.env'
    if (-not (Test-Path -LiteralPath $file)) { return '' }
    foreach ($line in [System.IO.File]::ReadAllLines($file)) {
        if ($line -match '^\s*APP_KEY\s*=\s*(.*)$') {
            return $Matches[1].Trim().Trim('"').Trim([char]39)
        }
    }
    return ''
}
