# ABAS — hent seneste kode fra GitHub og byg Tailwind CSS
# Kør fra projektmappen eller angiv -RepoPath
#
# Eksempel:
#   .\scripts\deploy.ps1
#   .\scripts\deploy.ps1 -RepoPath "C:\wamp64\www\TrekantBrand\Sandbox\ABAS"

param(
    [string]$RepoPath = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path,
    [string]$Branch = 'master'
)

function Invoke-NativeCommand {
    param(
        [string]$Label,
        [string]$FilePath,
        [string[]]$ArgumentList
    )

    Write-Host "`n$Label" -ForegroundColor Yellow

    # Git/npm skriver ofte til stderr uden at fejle (især i Windows PowerShell 5.1).
    $prevErrorAction = $ErrorActionPreference
    $ErrorActionPreference = 'Continue'
    try {
        & $FilePath @ArgumentList 2>&1 | ForEach-Object {
            if ($_ -is [System.Management.Automation.ErrorRecord]) {
                Write-Host $_.ToString()
            } else {
                Write-Host $_
            }
        }
    } finally {
        $ErrorActionPreference = $prevErrorAction
    }

    if ($LASTEXITCODE -ne 0) {
        throw "$FilePath fejlede (exit $LASTEXITCODE)."
    }
}

Write-Host "ABA Service deploy" -ForegroundColor Cyan
Write-Host "Mappe: $RepoPath" -ForegroundColor Gray
Set-Location $RepoPath

Invoke-NativeCommand -Label "[1/3] git pull origin $Branch" -FilePath 'git' -ArgumentList @('pull', 'origin', $Branch)

if (-not (Get-Command npm -ErrorAction SilentlyContinue)) {
    throw "npm blev ikke fundet. Installer Node.js (https://nodejs.org) og prøv igen."
}

Invoke-NativeCommand -Label '[2/3] npm install' -FilePath 'npm' -ArgumentList @('install')
Invoke-NativeCommand -Label '[3/3] npm run build (Tailwind CSS)' -FilePath 'npm' -ArgumentList @('run', 'build')

Write-Host "`nFærdig. CSS: public\assets\css\app.css" -ForegroundColor Green
