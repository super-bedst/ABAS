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
$sIns = 18381
$deal = 'TB'

$login = Invoke-RestMethod -Uri "$base/login/login" -Method POST -ContentType 'application/json' -Body (@{ loginName = $user.ToUpper(); loginPass = $pass } | ConvertTo-Json -Compress)
$headers = @{ 'User-Token' = [string]$login.message.token.result; 'Content-Type' = 'application/json' }
$body = @{ userid = $user.ToUpper(); s_ins = $sIns; deal_id = $deal; lines = 200; maxrows = 200 }

$procs = @(
    'g_ma_zonestatus', 'g_ma_zone_status', 'g_ma_zones', 'g_ma_zone', 'g_ma_installation_zones',
    'g_ma_ins_zones', 'g_ma_zoneinfo', 'g_ma_zone_sum', 'g_ma_zonesum', 'g_ma_panel_zones',
    'g_ma_zone_list', 'g_ma_zone_stat', 'g_ma_zonelist', 'g_ma_zone_status_sum'
)

foreach ($proc in $procs) {
    try {
        $resp = Invoke-RestMethod -Uri "$base/api/v1/$proc" -Method POST -Headers $headers -Body ($body | ConvertTo-Json -Compress)
        $count = if ($resp.ResultSet) { @($resp.ResultSet).Count } else { 0 }
        Write-Host "$proc RC=$($resp.ReturnCode) rows=$count" -ForegroundColor Green
        if ($count -gt 0) {
            $resp.ResultSet[0].PSObject.Properties.Name | Sort-Object | ForEach-Object { Write-Host "  $_" }
            Write-Host "`nSample rows:" -ForegroundColor Cyan
            @($resp.ResultSet | Select-Object -First 8) | ForEach-Object {
                Write-Host ("ix={0} zix={1} atext={2} area={3} earea={4} ecode={5} event={6} aplan={7} aplan_text={8} in_test={9} segrade={10}" -f $_.ix, $_.zix, ($_.atext -as [string]).Trim(), ($_.area -as [string]).Trim(), ($_.earea -as [string]).Trim(), ($_.ecode -as [string]).Trim(), ($_.event -as [string]).Trim(), ($_.aplan -as [string]).Trim(), ($_.aplan_text -as [string]).Trim(), $_.in_test_flg, ($_.segrade -as [string]).Trim())
            }
            break
        }
    } catch {
        $msg = $_.ErrorDetails.Message
        if ($msg -match '404|not found|Unknown') {
            Write-Host "$proc -> not found" -ForegroundColor DarkGray
        } else {
            Write-Host "$proc -> $($msg.Substring(0, [Math]::Min(120, $msg.Length)))" -ForegroundColor Yellow
        }
    }
}
