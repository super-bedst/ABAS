#Requires -Version 5.1
<#
.SYNOPSIS
  Testscript mod TrekantBrand API for ABA Service.

.DESCRIPTION
  Søger anlæg via miscno2 (fx FAB0100), sætter i test, tjekker status, stopper test og henter log.

.EXAMPLE
  .\scripts\trekant_test.ps1 start
  .\scripts\trekant_test.ps1 status
  .\scripts\trekant_test.ps1 stop
  .\scripts\trekant_test.ps1 log
  .\scripts\trekant_test.ps1 log -Lines 50

  Med credentials:
  $env:TREKANT_API_USER='NKI'; $env:TREKANT_API_PASS='***'; .\scripts\trekant_test.ps1 start

  Læser også .env i projektrod (KEY=value linjer).
#>
param(
    [Parameter(Position = 0)]
    [ValidateSet('start', 'status', 'stop', 'log', 'menu')]
    [string] $Action = 'menu',

    [string] $MiscNo2,
    [int] $Hours = 3,
    [int] $Lines = 20,
    [string] $UserId,
    [string] $Term,
    [string] $StartComment,
    [string] $StopComment
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

function Get-ProjectRoot {
    $root = Split-Path -Parent $PSScriptRoot
    if (-not (Test-Path (Join-Path $root 'docs\PLAN.md'))) {
        throw "Kunne ikke finde projektrod (docs\PLAN.md) fra $PSScriptRoot"
    }
    return $root
}

function Import-DotEnv {
    param([string] $Path)
    if (-not (Test-Path $Path)) { return }
    Get-Content $Path -Encoding UTF8 | ForEach-Object {
        $line = $_.Trim()
        if ($line -eq '' -or $line.StartsWith('#')) { return }
        if ($line -match '^\s*([^#=]+?)\s*=\s*(.*)\s*$') {
            $key = $matches[1].Trim()
            $val = $matches[2].Trim().Trim('"').Trim("'")
            if (-not [string]::IsNullOrWhiteSpace($key) -and -not $env:$key) {
                Set-Item -Path "env:$key" -Value $val
            }
        }
    }
}

function Get-Config {
    $root = Get-ProjectRoot
    Import-DotEnv (Join-Path $root '.env')
    Import-DotEnv (Join-Path $root '.env.local')

    $user = if ($UserId) { $UserId } else { $env:TREKANT_API_USER }
    $pass = $env:TREKANT_API_PASS
    $base = if ($env:TREKANT_API_URL) { $env:TREKANT_API_URL.TrimEnd('/') } else { 'https://api.trekantbrand.dk' }
    $misc = if ($MiscNo2) { $MiscNo2 } else { if ($env:TREKANT_TEST_MISCNO2) { $env:TREKANT_TEST_MISCNO2 } else { 'fab0100' } }
    $term = if ($Term) { $Term } else { if ($env:TREKANT_TERM) { $env:TREKANT_TERM } else { 'ABAS' } }
    $userLabel = if ($env:TREKANT_TEST_USER_LABEL) { $env:TREKANT_TEST_USER_LABEL } else { 'Bruger Peter Friis 20654196 Trekantbrand' }

    if ([string]::IsNullOrWhiteSpace($user)) { throw "Mangler TREKANT_API_USER (miljøvariabel eller -UserId)" }
    if ([string]::IsNullOrWhiteSpace($pass)) { throw "Mangler TREKANT_API_PASS (miljøvariabel eller .env)" }

    @{
        BaseUrl     = $base
        User        = $user.ToUpper()
        Pass        = $pass
        MiscNo2     = $misc
        Term        = $term
        UserLabel   = $userLabel
        StateFile   = Join-Path $root (Join-Path 'scripts' '.trekant_test_state.json')
    }
}

function Invoke-TrekantLogin {
    param($Config)
    $body = @{ loginName = $Config.User; loginPass = $Config.Pass } | ConvertTo-Json -Compress
    $resp = Invoke-RestMethod -Uri "$($Config.BaseUrl)/login/login" -Method POST -ContentType 'application/json' -Body $body
    if (-not $resp.success) {
        throw "Login fejlede: $($resp.message)"
    }
    return [string]$resp.message.token.result
}

function Invoke-TrekantApi {
    param(
        $Config,
        [string] $Token,
        [string] $Procedure,
        [hashtable] $Payload
    )
    $headers = @{
        'User-Token'   = $Token
        'Content-Type' = 'application/json'
    }
    $json = $Payload | ConvertTo-Json -Compress
    try {
        return Invoke-RestMethod -Uri "$($Config.BaseUrl)/api/v1/$Procedure" -Method POST -Headers $headers -Body $json
    } catch {
        $detail = $_.ErrorDetails.Message
        if ($detail) {
            throw "API $Procedure fejlede: $detail"
        }
        throw
    }
}

function Save-InstallationState {
    param($Config, $Installation, [int] $SInc = 0)
    $state = @{
        miscno2  = $Installation.miscno2
        ins_no   = $Installation.ins_no
        s_ins    = [int]$Installation.insid
        deal_id  = [string]$Installation.deal_id
        name     = $Installation.name
        s_inc    = $SInc
        saved_at = (Get-Date).ToString('o')
    }
    $state | ConvertTo-Json -Depth 4 | Set-Content -Path $Config.StateFile -Encoding UTF8
    return $state
}

function Get-InstallationState {
    param($Config)
    if (-not (Test-Path $Config.StateFile)) { return $null }
    return Get-Content $Config.StateFile -Raw -Encoding UTF8 | ConvertFrom-Json
}

function Resolve-Installation {
    param($Config, [string] $Token)
    $state = Get-InstallationState $Config
    if ($state -and $state.miscno2 -ieq $Config.MiscNo2) {
        return $state
    }

    $resp = Invoke-TrekantApi -Config $Config -Token $Token -Procedure 'g_search_installations' -Payload @{
        userid  = $Config.User
        miscno2 = $Config.MiscNo2
        maxrows = 100
    }

    if ($resp.ReturnCode -ne 0) {
        throw "g_search_installations ReturnCode $($resp.ReturnCode)"
    }
    if (-not $resp.ResultSet -or @($resp.ResultSet).Count -eq 0) {
        throw "Anlæg ikke fundet for miscno2=$($Config.MiscNo2)"
    }

    $row = @($resp.ResultSet | Where-Object { $_.miscno2 -ieq $Config.MiscNo2 } | Select-Object -First 1)
    if (-not $row) {
        $row = $resp.ResultSet | Select-Object -First 1
    }

    return Save-InstallationState -Config $Config -Installation $row
}

function Format-TestTime {
    param([int] $Hours)
    if ($Hours -lt 0 -or $Hours -gt 9999) { throw "Hours skal være 0-9999" }
    return ('0000:{0:D2}:00:00' -f $Hours)
}

function Show-Installation {
    param($Installation)
    Write-Host ''
    Write-Host 'Anlæg' -ForegroundColor Cyan
    Write-Host "  miscno2 : $($Installation.miscno2)"
    Write-Host "  ins_no  : $($Installation.ins_no)"
    Write-Host "  s_ins   : $($Installation.s_ins)"
    Write-Host "  deal_id : $($Installation.deal_id)"
    if ($Installation.name) { Write-Host "  navn    : $($Installation.name)" }
    Write-Host ''
}

function Invoke-StartTest {
    param($Config, [string] $Token, $Installation, [string] $Comment)
    $testTime = Format-TestTime -Hours $Hours
    $comm = if ($Comment) { $Comment } else { "TEST START ABAS $($Config.UserLabel)" }
    if ($comm.Length -gt 80) {
        Write-Warning "Kommentar trimmes til 80 tegn (API-grænse)."
        $comm = $comm.Substring(0, 80)
    }

    Write-Host "Starter test i $Hours time(r) ($testTime)..." -ForegroundColor Yellow
    Write-Host "Kommentar: $comm"

    $resp = Invoke-TrekantApi -Config $Config -Token $Token -Procedure 'c_ma_testqueue' -Payload @{
        userid    = $Config.User
        s_ins     = [int]$Installation.s_ins
        deal_id   = [string]$Installation.deal_id
        test_time = $testTime
        comm      = $comm
        zoneix    = -1
        term      = $Config.Term
    }

    Write-Host "ReturnCode: $($resp.ReturnCode)"
    if ($resp.ReturnCode -eq 15997) {
        Write-Warning 'Anlæg er allerede i test (15997). Brug status for at se tilstand.'
    } elseif ($resp.ReturnCode -ne 0) {
        throw "Start test fejlede med ReturnCode $($resp.ReturnCode)"
    }

    $sInc = 0
    if ($resp.ResultSet -and @($resp.ResultSet).Count -gt 0) {
        $sInc = [int]$resp.ResultSet[0]
        Write-Host "s_inc (test-hændelse): $sInc" -ForegroundColor Green
    }

    Save-InstallationState -Config $Config -Installation ([pscustomobject]@{
        miscno2 = $Installation.miscno2
        ins_no  = $Installation.ins_no
        insid   = $Installation.s_ins
        deal_id = $Installation.deal_id
        name    = $Installation.name
    }) -SInc $sInc | Out-Null
}

function Invoke-StatusTest {
    param($Config, [string] $Token, $Installation)
    Write-Host 'Henter test-status (g_ma_testqueue)...' -ForegroundColor Yellow
    $resp = Invoke-TrekantApi -Config $Config -Token $Token -Procedure 'g_ma_testqueue' -Payload @{
        userid  = $Config.User
        s_ins   = [int]$Installation.s_ins
        deal_id = [string]$Installation.deal_id
        lines   = 20
    }

    Write-Host "ReturnCode: $($resp.ReturnCode)"
    if (-not $resp.ResultSet -or @($resp.ResultSet).Count -eq 0) {
        Write-Host 'Ingen aktiv testkø-data fundet (anlæg er muligvis ikke i test).' -ForegroundColor DarkYellow
        return
    }

    $rows = @($resp.ResultSet)
    Write-Host "Antal rækker: $($rows.Count)" -ForegroundColor Green
    $rows | Select-Object -First 10 | ForEach-Object {
        $props = $_ | Get-Member -MemberType NoteProperty | Select-Object -ExpandProperty Name
        $summary = @()
        foreach ($p in @('tm_date', 'tm_time', 'event', 'comm_gen', 'operator', 'text', 'zone_text')) {
            if ($props -contains $p -and $_.$p) {
                $summary += "$p=$($_.$p)"
            }
        }
        if ($summary.Count -eq 0) {
            $_ | ConvertTo-Json -Compress
        } else {
            ($summary -join ' | ')
        }
    } | ForEach-Object { Write-Host "  $_" }
}

function Invoke-StopTest {
    param($Config, [string] $Token, $Installation, [string] $Comment)
    $comment = if ($Comment) { $Comment } else { "TEST STOP ABAS $($Config.UserLabel)" }
    if ($comment.Length -gt 80) {
        Write-Warning "Kommentar trimmes til 80 tegn (API-grænse)."
        $comment = $comment.Substring(0, 80)
    }

    Write-Host 'Stopper test (d_ma_testqueue)...' -ForegroundColor Yellow
    Write-Host "Kommentar: $comment"

    $payload = @{
        userid  = $Config.User
        s_ins   = [int]$Installation.s_ins
        deal_id = [string]$Installation.deal_id
        term    = $Config.Term
        comment = $comment
    }
    if ($Installation.s_inc -and [int]$Installation.s_inc -gt 0) {
        $payload.s_inc = [int]$Installation.s_inc
    }

    $resp = Invoke-TrekantApi -Config $Config -Token $Token -Procedure 'd_ma_testqueue' -Payload $payload
    Write-Host "ReturnCode: $($resp.ReturnCode)"
    switch ($resp.ReturnCode) {
        0 { Write-Host 'Test stoppet.' -ForegroundColor Green }
        15974 { Write-Warning 'Anlæg var ikke i test (15974).' }
        16840 { Write-Warning 'Zone restore påkrævet før stop (16840).' }
        default { throw "Stop test fejlede med ReturnCode $($resp.ReturnCode)" }
    }
}

function Invoke-FetchLog {
    param($Config, [string] $Token, $Installation)
    Write-Host "Henter log (g_ma_alarmlog, lines=$Lines)..." -ForegroundColor Yellow
    $resp = Invoke-TrekantApi -Config $Config -Token $Token -Procedure 'g_ma_alarmlog' -Payload @{
        userid  = $Config.User
        s_ins   = [int]$Installation.s_ins
        deal_id = [string]$Installation.deal_id
        lines   = $Lines
    }

    Write-Host "ReturnCode: $($resp.ReturnCode)"
    if (-not $resp.ResultSet -or @($resp.ResultSet).Count -eq 0) {
        Write-Host 'Ingen loglinjer returneret.'
        return
    }

    $table = @($resp.ResultSet | ForEach-Object {
        [PSCustomObject]@{
            Dato      = $_.tm_date
            Tid       = $_.tm_time
            Event     = if ($_.event) { $_.event.Trim() } else { '' }
            Kommentar = $_.comm_gen
            Tekst     = if ($_.text) { $_.text.Trim() } elseif ($_.zone_text) { $_.zone_text.Trim() } else { '' }
            Operatør  = if ($_.operator) { $_.operator.Trim() } else { '' }
        }
    })

    $table | Format-Table -AutoSize -Wrap | Out-String -Width 220 | Write-Host
}

function Show-Menu {
    Write-Host ''
    Write-Host 'TrekantBrand ABA testscript' -ForegroundColor Cyan
    Write-Host '  1  Start test (3 timer + kommentar)'
    Write-Host '  2  Status'
    Write-Host '  3  Stop test (med kommentar)'
    Write-Host '  4  Hent log (sidste 20)'
    Write-Host '  Q  Afslut'
    Write-Host ''
    $choice = Read-Host 'Vælg'
    switch ($choice.ToUpper()) {
        '1' { return 'start' }
        '2' { return 'status' }
        '3' { return 'stop' }
        '4' { return 'log' }
        default { return 'quit' }
    }
}

$config = Get-Config
Write-Host "API: $($config.BaseUrl) | bruger: $($config.User) | anlæg: $($config.MiscNo2)" -ForegroundColor DarkGray

$token = Invoke-TrekantLogin -Config $config
$installation = Resolve-Installation -Config $config -Token $token
Show-Installation $installation

do {
    $run = if ($Action -eq 'menu') { Show-Menu } else { $Action; $Action = 'quit' }
    switch ($run) {
        'start' {
            Invoke-StartTest -Config $config -Token $token -Installation $installation -Comment $StartComment
            $installation = Get-InstallationState $config
        }
        'status' { Invoke-StatusTest -Config $config -Token $token -Installation $installation }
        'stop' {
            $installation = Get-InstallationState $config
            if (-not $installation) { $installation = Resolve-Installation -Config $config -Token $token }
            Invoke-StopTest -Config $config -Token $token -Installation $installation -Comment $StopComment
        }
        'log' { Invoke-FetchLog -Config $config -Token $token -Installation $installation }
        'quit' { break }
        default { Write-Host 'Ukendt valg.' -ForegroundColor Red }
    }
} while ($Action -eq 'menu' -and $run -ne 'quit')

Write-Host 'Færdig.' -ForegroundColor DarkGray
