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
$misc = if ($args[0]) { $args[0] } else { 'fab0100' }

$loginBody = @{ loginName = $user.ToUpper(); loginPass = $pass } | ConvertTo-Json -Compress
$login = Invoke-RestMethod -Uri "$base/login/login" -Method POST -ContentType 'application/json' -Body $loginBody
$token = [string]$login.message.token.result

$searchBody = @{ userid = $user.ToUpper(); miscno2 = $misc.ToLower(); maxrows = 5 } | ConvertTo-Json -Compress
$search = Invoke-RestMethod -Uri "$base/api/v1/g_search_installations" -Method POST -Headers @{ 'User-Token' = $token; 'Content-Type' = 'application/json' } -Body $searchBody
$row = @($search.ResultSet | Where-Object { $_.miscno2 -ieq $misc } | Select-Object -First 1)
if (-not $row) { $row = $search.ResultSet | Select-Object -First 1 }
if (-not $row) { throw "Anlæg $misc ikke fundet" }

Write-Host "Anlæg: $($row.miscno2) s_ins=$($row.insid) deal_id=$($row.deal_id)" -ForegroundColor Cyan

$logBody = @{ userid = $user.ToUpper(); s_ins = [int]$row.insid; deal_id = [string]$row.deal_id; lines = 500 } | ConvertTo-Json -Compress
$log = Invoke-RestMethod -Uri "$base/api/v1/g_ma_alarmlog" -Method POST -Headers @{ 'User-Token' = $token; 'Content-Type' = 'application/json' } -Body $logBody

Write-Host "ReturnCode: $($log.ReturnCode)" -ForegroundColor Yellow
$rows = @($log.ResultSet)
Write-Host "Rækker: $($rows.Count)`n" -ForegroundColor Yellow

if ($rows.Count -gt 0) {
    $props = $rows[0].PSObject.Properties.Name | Sort-Object
    Write-Host "Felter i første række ($($props.Count)):" -ForegroundColor Green
    $props | ForEach-Object { Write-Host "  $_" }
    Write-Host "`n--- Første 5 rækker (alle felter med værdi) ---" -ForegroundColor Green
    $rows | Select-Object -First 5 | ForEach-Object -Begin { $i = 0 } -Process {
        $i++
        Write-Host "`n#$i $($_.tm_date) $($_.tm_time) event=$($_.event)" -ForegroundColor Cyan
        $_.PSObject.Properties | Where-Object { $null -ne $_.Value -and "$($_.Value)".Trim() -ne '' } | ForEach-Object {
            Write-Host ("  {0,-18} = {1}" -f $_.Name, $_.Value)
        }
    }
}

# Gem rå JSON til analyse
$out = Join-Path $root 'scripts\.alarmlog_sample.json'
@($rows | Select-Object -First 20) | ConvertTo-Json -Depth 6 | Set-Content -Path $out -Encoding UTF8
Write-Host "`nGemt: $out" -ForegroundColor DarkGray

$zoneRows = @($rows | Where-Object { [int]$_.zone -gt 0 -or ($_.text -and $_.text.Trim() -ne '') -or ($_.event -match 'ALARM|RESTORE') })
Write-Host "`nRækker med zone>0, text eller ALARM/RESTORE: $($zoneRows.Count)" -ForegroundColor Magenta
$zoneRows | Select-Object -First 8 | ForEach-Object {
    Write-Host ("  {0} {1} | event={2} | zone={3} | zone_text={4} | text={5} | comm_gen={6}" -f $_.tm_date, $_.tm_time, $_.event.Trim(), $_.zone, $_.zone_text.Trim(), $_.text.Trim(), $_.comm_gen.Trim())
}

$grouped = $rows | Group-Object s_inc | Sort-Object { [int]$_.Name } -Descending | Select-Object -First 3
Write-Host "`nEksempel: flere linjer pr. s_inc (samme hændelse):" -ForegroundColor Magenta
foreach ($g in $grouped) {
    Write-Host "  s_inc=$($g.Name) ($($g.Count) linjer)" -ForegroundColor Cyan
    $g.Group | ForEach-Object {
        Write-Host ("    tmod={0} event={1} comm_gen={2} text={3}" -f $_.tmod, $_.event.Trim(), $_.comm_gen.Trim(), $_.text.Trim())
    }
}
