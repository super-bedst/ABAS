#Requires -Version 5.1
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
if (-not $user -or -not $pass) { throw 'Mangler TREKANT_API_USER/PASS i env.local' }

Write-Host "API: $base | bruger: $user" -ForegroundColor Cyan

$loginBody = @{ loginName = $user.ToUpper(); loginPass = $pass } | ConvertTo-Json -Compress
$login = Invoke-RestMethod -Uri "$base/login/login" -Method POST -ContentType 'application/json' -Body $loginBody
if (-not $login.success) { throw "Login fejlede: $($login.message)" }
$token = [string]$login.message.token.result
Write-Host "Login OK" -ForegroundColor Green

function Search-MiscNo2 {
    param([string] $Misc)
    $headers = @{ 'User-Token' = $token; 'Content-Type' = 'application/json' }
    $body = @{ userid = $user.ToUpper(); miscno2 = $Misc.ToLower(); maxrows = 100 } | ConvertTo-Json -Compress
    $resp = Invoke-RestMethod -Uri "$base/api/v1/g_search_installations" -Method POST -Headers $headers -Body $body
    $rows = @()
    if ($resp.ResultSet) { $rows = @($resp.ResultSet) }
  return [PSCustomObject]@{
        miscno2     = $Misc
        returnCode  = [int]$resp.ReturnCode
        rowCount    = $rows.Count
        sample      = ($rows | Select-Object -First 3 -Property miscno2, ins_no, name, deal_id)
    }
}

$tests = @('fab0100', 'fab00', 'fab01', 'fab02', 'fab99')
Write-Host "`nBatch-søgetest:" -ForegroundColor Yellow
foreach ($t in $tests) {
    $r = Search-MiscNo2 -Misc $t
    Write-Host ("  {0,-8} ReturnCode={1} rows={2}" -f $r.miscno2, $r.returnCode, $r.rowCount)
    if ($r.sample) {
        $r.sample | ForEach-Object {
            Write-Host ("    -> {0} ({1}) {2}" -f $_.miscno2, $_.ins_no, $_.name) -ForegroundColor DarkGray
        }
    }
}

Write-Host "`nSimuleret sync for prefix fab (max 9999) = 100 batch-kald:" -ForegroundColor Yellow
$totalRows = 0
$totalUpsertable = 0
$apiCalls = 0
$searchKeys = 0..99 | ForEach-Object { 'fab' + ('{0:D2}' -f $_) }
foreach ($key in $searchKeys) {
    $headers = @{ 'User-Token' = $token; 'Content-Type' = 'application/json' }
    $body = @{ userid = $user.ToUpper(); miscno2 = $key; maxrows = 100 } | ConvertTo-Json -Compress
    $resp = Invoke-RestMethod -Uri "$base/api/v1/g_search_installations" -Method POST -Headers $headers -Body $body
    $apiCalls++
    if ([int]$resp.ReturnCode -ne 0) {
        Write-Host "  $key ReturnCode $($resp.ReturnCode)" -ForegroundColor Red
        continue
    }
    $rows = @()
    if ($resp.ResultSet) { $rows = @($resp.ResultSet) }
    $totalRows += $rows.Count
    $withDeal = @($rows | Where-Object { $_.deal_id -and ($_.insid -or $_.s_ins) })
    $totalUpsertable += $withDeal.Count
    if ($rows.Count -ge 100) {
        Write-Host "  ADVARSEL: $key returnerede 100 rækker" -ForegroundColor Red
    }
}
Write-Host "  API-kald: $apiCalls" -ForegroundColor Green
Write-Host "  Rækker modtaget: $totalRows" -ForegroundColor Green
Write-Host "  Rækker med insid+deal_id: $totalUpsertable" -ForegroundColor Green
