#Requires -Version 7.0
[CmdletBinding()]
param(
    [string]$BindHost = '127.0.0.1'
)

$ErrorActionPreference = 'Stop'
Set-Location -LiteralPath $PSScriptRoot

$php = Get-Command php -ErrorAction Stop
$cloudflared = Get-Command cloudflared -ErrorAction Stop

$port = 8000
$appUrl = Select-String -Path .env -Pattern '^APP_URL=' -ErrorAction SilentlyContinue
if ($appUrl) {
    $m = [regex]::Match($appUrl.Line, 'http://[^:]+:(\d+)')
    if ($m.Success) { $port = [int]$m.Groups[1].Value }
}

Write-Host "==> Build aset frontend (npm run build)..." -ForegroundColor Cyan
npm run build
if ($LASTEXITCODE -ne 0) { throw "npm run build gagal" }
Write-Host "==> Aset selesai dibangun." -ForegroundColor Cyan

$hotFile = Join-Path $PSScriptRoot 'public\hot'
if (Test-Path $hotFile) {
    Write-Warning "Ditemukan sisa public\hot (dari 'npm run dev'). Menghapus agar aset memakai manifest build, bukan dev-server Vite."
    Remove-Item -LiteralPath $hotFile -Force
}

$tempDir = Join-Path $env:TEMP 'mjcc-serve-public'
New-Item -ItemType Directory -Force -Path $tempDir | Out-Null
$serverOut  = Join-Path $tempDir 'server.log'
$serverErr  = Join-Path $tempDir 'server.err.log'
$tunnelOut  = Join-Path $tempDir 'tunnel.log'
$tunnelErr  = Join-Path $tempDir 'tunnel.err.log'
Remove-Item $serverOut, $serverErr, $tunnelOut, $tunnelErr -Force -ErrorAction SilentlyContinue

$procs = @()
function Stop-All {
    foreach ($p in $procs) {
        if ($p -and -not $p.HasExited) { Stop-Process -Id $p.Id -Force -ErrorAction SilentlyContinue }
    }
}

try {
    Write-Host "==> Menjalankan server lokal di http://$BindHost`:$port ..." -ForegroundColor Cyan
    $srv = Start-Process -FilePath $php.Source `
        -ArgumentList @('artisan', 'serve', "--host=$BindHost", "--port=$port") `
        -WorkingDirectory $PSScriptRoot `
        -RedirectStandardOutput $serverOut -RedirectStandardError $serverErr -PassThru
    $procs += $srv

    Write-Host "==> Membuka tunnel publik via cloudflared ..." -ForegroundColor Cyan
    $tnl = Start-Process -FilePath $cloudflared.Source `
        -ArgumentList @('tunnel', '--no-autoupdate', '--url', "http://$BindHost`:$port") `
        -WorkingDirectory $PSScriptRoot `
        -RedirectStandardOutput $tunnelOut -RedirectStandardError $tunnelErr -PassThru
    $procs += $tnl

    $deadline = (Get-Date).AddSeconds(45)
    $url = $null
    while ((Get-Date) -lt $deadline) {
        if ($srv.HasExited) { throw "Server artisan berhenti. Lihat $serverErr" }
        if ($tnl.HasExited) { throw "cloudflared berhenti. Lihat $tunnelErr" }
        Start-Sleep -Milliseconds 500
        $logFiles = @($tunnelOut, $tunnelErr) | Where-Object { Test-Path -LiteralPath $_ }
        $hit = Select-String -Path $logFiles -Pattern 'https://[-a-z0-9]+\.trycloudflare\.com' -ErrorAction SilentlyContinue | Select-Object -Last 1
        if ($hit) { $url = $hit.Matches[0].Value; break }
    }

    if (-not $url) {
        Write-Warning "URL publik belum muncul dalam 45 detik. Periksa: $tunnelOut"
    } else {
        Write-Host ""
        Write-Host "  =============================================" -ForegroundColor Green
        Write-Host "   SITE PUBLIC: $url" -ForegroundColor Green
        Write-Host "  =============================================" -ForegroundColor Green
        Write-Host ""
        Write-Host "  Server lokal : http://$BindHost`:$port" -ForegroundColor DarkGray
        Write-Host "  (Trusted proxies 127.0.0.1/::1 aktif: aset & route dibuat https via header tunnel.)" -ForegroundColor DarkGray
        Write-Host "  Press Ctrl+C untuk menghentikan server & tunnel." -ForegroundColor DarkGray
        Write-Host ""
    }

    while (-not ($srv.HasExited -or $tnl.HasExited)) {
        if ([Console]::KeyAvailable) {
            $key = [Console]::ReadKey($true)
            if ($key.Modifiers -band [ConsoleModifiers]::Control -and $key.Key -eq [ConsoleKey]::C) { break }
        }
        Start-Sleep -Milliseconds 400
    }
}
finally {
    Stop-All
    Write-Host ""
    Write-Host "==> Server & tunnel dihentikan."
    Write-Host "    Log: $tempDir"
}