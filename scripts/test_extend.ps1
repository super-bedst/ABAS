#Requires -Version 5.1
<#
.SYNOPSIS
  Test forlængelse af aktiv testkø via c_ma_testqueue_remaining.

.EXAMPLE
  .\scripts\test_extend.ps1 fab0100 2
#>
Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

$root = Split-Path -Parent $PSScriptRoot
foreach ($file in @('env.local', '.env.local', '.env')) {
    $path = Join-Path $root $file
    if (-not (Test-Path $path)) { continue }
    Get-Content $path -Encoding UTF8 | ForEach-Object {
        $line = $_.Trim()
        if ($line -eq '' -or $line.StartsWith('#')) { return }
        if ($line -match '^\s*([^#=]+?)\s*=\s*(.*)\s*$') {
            $key = $matches[1].Trim()
            $val = $matches[2].Trim().Trim('"').Trim("'")
            if ($key -and -not (Get-Item -Path "env:$key" -ErrorAction SilentlyContinue)) {
                Set-Item -Path "env:$key" -Value $val
            }
        }
    }
}

$user = $env:TREKANT_API_USER
$pass = $env:TREKANT_API_PASS
$base = if ($env:TREKANT_API_URL) { $env:TREKANT_API_URL.TrimEnd('/') } else { 'https://api.trekantbrand.dk' }
$misc = if ($args[0]) { $args[0] } else { 'fab0100' }
$addHours = if ($args[1]) { [double]$args[1] } else { 2.0 }

$login = Invoke-RestMethod -Uri "$base/login/login" -Method POST -ContentType 'application/json' -Body (@{ loginName = $user.ToUpper(); loginPass = $pass } | ConvertTo-Json -Compress)
$token = [string]$login.message.token.result
$headers = @{ 'User-Token' = $token; 'Content-Type' = 'application/json' }

$search = Invoke-RestMethod -Uri "$base/api/v1/g_search_installations" -Method POST -Headers $headers -Body (@{ userid = $user.ToUpper(); miscno2 = $misc.ToLower(); maxrows = 5 } | ConvertTo-Json -Compress)
$row = @($search.ResultSet | Where-Object { $_.miscno2 -ieq $misc } | Select-Object -First 1)[0]
if (-not $row) { throw "Anlæg $misc ikke fundet" }

$sIns = [int]$row.insid
$deal = [string]$row.deal_id
Write-Host "Anlæg: $misc s_ins=$sIns" -ForegroundColor Cyan

$status = Invoke-RestMethod -Uri "$base/api/v1/g_ma_testqueue" -Method POST -Headers $headers -Body (@{ userid = $user.ToUpper(); s_ins = $sIns; deal_id = $deal; lines = 5 } | ConvertTo-Json -Compress)
if (-not $status.ResultSet -or @($status.ResultSet).Count -eq 0) {
    throw 'Anlæg er ikke i test — start service først.'
}
$sInc = [int]$status.ResultSet[0].s_inc
Write-Host "Aktiv s_inc=$sInc" -ForegroundColor Yellow

$queryBody = @{ userid = $user.ToUpper(); s_ins = $sIns; deal_id = $deal; s_inc = $sInc; test_time = '0000:00:00:00' }
$before = Invoke-RestMethod -Uri "$base/api/v1/c_ma_testqueue_remaining" -Method POST -Headers $headers -Body ($queryBody | ConvertTo-Json -Compress)
$timRem = [int]$before.ResultSet[0].tim_rem
Write-Host "Resterende før (tim_rem): $timRem sek ($([Math]::Round($timRem/60,1)) min)" -ForegroundColor Yellow

$addSeconds = [int][Math]::Round($addHours * 3600)
$newSeconds = $timRem + $addSeconds
$h = [Math]::DivRem($newSeconds, 3600, [ref]$null)
$rem = $newSeconds % 3600
$m = [Math]::DivRem($rem, 60, [ref]$null)
$s = $rem % 60
$testTime = ('0000:{0:D2}:{1:D2}:{2:D2}' -f $h, $m, $s)
Write-Host "Forlænger med $addHours t -> ny test_time=$testTime ($newSeconds sek) via c_ma_testqueue + s_inc" -ForegroundColor Cyan

$extendBody = @{
    userid    = $user.ToUpper()
    s_ins     = $sIns
    deal_id   = $deal
    s_inc     = $sInc
    test_time = $testTime
    comm      = 'ABA extend test script'
    zoneix    = -1
    term      = if ($env:TREKANT_TERM) { $env:TREKANT_TERM } else { 'ABAS' }
}
$extend = Invoke-RestMethod -Uri "$base/api/v1/c_ma_testqueue" -Method POST -Headers $headers -Body ($extendBody | ConvertTo-Json -Compress)
Write-Host "ReturnCode: $($extend.ReturnCode)" -ForegroundColor $(if ($extend.ReturnCode -eq 0) { 'Green' } else { 'Red' })

$after = Invoke-RestMethod -Uri "$base/api/v1/c_ma_testqueue_remaining" -Method POST -Headers $headers -Body ($queryBody | ConvertTo-Json -Compress)
$timAfter = [int]$after.ResultSet[0].tim_rem
Write-Host "Resterende efter (tim_rem): $timAfter sek (delta $($timAfter - $timRem) sek)" -ForegroundColor Yellow

if ($extend.ReturnCode -ne 0) {
    throw "Forlængelse fejlede med ReturnCode $($extend.ReturnCode)"
}
if ($timAfter -le $timRem) {
    Write-Warning 'tim_rem steg ikke — tjek at anlægget er i aktiv testkø.'
}
