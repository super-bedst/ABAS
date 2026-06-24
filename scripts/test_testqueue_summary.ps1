#Requires -Version 5.1
<#
.SYNOPSIS
  Test g_ma_testqueue_summary med forskellige parameterkombinationer.

.EXAMPLE
  .\scripts\test_testqueue_summary.ps1
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
if (-not $user -or -not $pass) { throw 'Mangler TREKANT_API_USER/PASS i env.local' }

$loginBody = @{ loginName = $user.ToUpper(); loginPass = $pass } | ConvertTo-Json -Compress
$login = Invoke-RestMethod -Uri "$base/login/login" -Method POST -ContentType 'application/json' -Body $loginBody
$token = [string]$login.message.token.result
$headers = @{ 'User-Token' = $token; 'Content-Type' = 'application/json' }

function Invoke-Summary {
    param([string]$Label, [hashtable]$Body)
    Write-Host "`n=== $Label ===" -ForegroundColor Cyan
    Write-Host ($Body | ConvertTo-Json -Compress)
    try {
        $resp = Invoke-WebRequest -Uri "$base/api/v1/g_ma_testqueue_summary" -Method POST -Headers $headers -Body ($Body | ConvertTo-Json -Compress) -UseBasicParsing
        $json = $resp.Content | ConvertFrom-Json
        $rc = if ($null -ne $json.ReturnCode) { $json.ReturnCode } else { $json.returnCode }
        $rows = @($json.ResultSet)
        Write-Host "HTTP $($resp.StatusCode) ReturnCode=$rc rows=$($rows.Count)" -ForegroundColor Green
        return @{ ok = $true; rc = $rc; rows = $rows.Count }
    } catch {
        $msg = $_.Exception.Message
        if ($_.Exception.Response) {
            try {
                $reader = New-Object System.IO.StreamReader($_.Exception.Response.GetResponseStream())
                $msg = $reader.ReadToEnd()
                $reader.Close()
            } catch {}
        }
        Write-Host "FEJL: $($msg.Substring(0, [Math]::Min(200, $msg.Length)))" -ForegroundColor Red
        return @{ ok = $false }
    }
}

$uid = $user.ToUpper()
Invoke-Summary -Label 'kun userid (fejler)' -Body @{ userid = $uid } | Out-Null
Invoke-Summary -Label 'minimum (anbefalet)' -Body @{ userid = $uid; s_ins = 0; deal_id = 'TB' } | Out-Null
Invoke-Summary -Label 'fuld dokumentation' -Body @{
    userid = $uid; noaccess = 0; noprofile = 0; s_ins = 0; deal_id = 'TB'
    s_inc = -1; scrolldir = 1; tstrun = 1; debug = 0
} | Out-Null
