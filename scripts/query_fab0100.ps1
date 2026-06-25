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
$misc = 'fab0100'

$loginBody = @{ loginName = $user.ToUpper(); loginPass = $pass } | ConvertTo-Json -Compress
$login = Invoke-RestMethod -Uri "$base/login/login" -Method POST -ContentType 'application/json' -Body $loginBody
$token = [string]$login.message.token.result
$headers = @{ 'User-Token' = $token; 'Content-Type' = 'application/json' }

$searchBody = @{ userid = $user.ToUpper(); miscno2 = $misc; maxrows = 5 } | ConvertTo-Json -Compress
$search = Invoke-RestMethod -Uri "$base/api/v1/g_search_installations" -Method POST -Headers $headers -Body $searchBody

$row = @($search.ResultSet | Where-Object { $_.miscno2 -ieq $misc } | Select-Object -First 1)
if (-not $row) { $row = $search.ResultSet | Select-Object -First 1 }
if (-not $row) { throw "fab0100 ikke fundet" }

$sIns = [int]$row.insid
$deal = [string]$row.deal_id

$details = Invoke-RestMethod -Uri "$base/api/v1/g_ma_installations" -Method POST -Headers $headers -Body (@{ s_ins = $sIns; deal_id = $deal } | ConvertTo-Json -Compress)
$testq = Invoke-RestMethod -Uri "$base/api/v1/g_ma_testqueue" -Method POST -Headers $headers -Body (@{ s_ins = $sIns; deal_id = $deal; lines = 3 } | ConvertTo-Json -Compress)
$log = Invoke-RestMethod -Uri "$base/api/v1/g_ma_alarmlog" -Method POST -Headers $headers -Body (@{ userid = $user.ToUpper(); s_ins = $sIns; deal_id = $deal; lines = 5 } | ConvertTo-Json -Compress)
$zones = Invoke-RestMethod -Uri "$base/api/v1/g_ma_zone" -Method POST -Headers $headers -Body (@{ userid = $user.ToUpper(); s_ins = $sIns; deal_id = $deal; lines = 200; maxrows = 200 } | ConvertTo-Json -Compress)

$out = [ordered]@{
    queried_at          = (Get-Date).ToString('o')
    search_return_code  = $search.ReturnCode
    installation        = $row
    details_return_code = $details.ReturnCode
    details             = $details.ResultSet
    testqueue_return_code = $testq.ReturnCode
    testqueue           = $testq.ResultSet
    alarmlog_return_code  = $log.ReturnCode
    alarmlog            = @($log.ResultSet | Select-Object -First 5)
    zones_return_code   = $zones.ReturnCode
    zones               = $zones.ResultSet
}

$outFile = Join-Path $PSScriptRoot '.fab0100_api_summary.json'
$out | ConvertTo-Json -Depth 10 | Set-Content -Path $outFile -Encoding UTF8
Write-Host "Gemt: $outFile"
Write-Host "s_ins=$sIns deal_id=$deal miscno2=$($row.miscno2)"
