# ABAS — hent seneste kode fra GitHub og byg Tailwind CSS
# Kør fra projektmappen eller angiv -RepoPath
#
# Eksempel:
#   .\scripts\deploy.ps1
#   .\scripts\deploy.ps1 -RepoPath "C:\wamp64\www\TrekantBrand\Sandbox\ABAS"
#   .\scripts\deploy.ps1 -Force
#
# -Force: Afbryd uafsluttet merge og overskriv lokale Ã¦ndringer med origin/master.

param(
    [string]$RepoPath = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path,
    [string]$Branch = 'master',
    [switch]$Force
)

function Write-NativeOutput {
    process {
        if ($_ -is [System.Management.Automation.ErrorRecord]) {
            Write-Host $_.ToString()
        } else {
            Write-Host $_
        }
    }
}

function Invoke-NativeCommand {
    param(
        [string]$Label,
        [string]$FilePath,
        [string[]]$ArgumentList,
        [switch]$AllowFailure
    )

    Write-Host "`n$Label" -ForegroundColor Yellow

    # Git/npm skriver ofte til stderr uden at fejle (især i Windows PowerShell 5.1).
    $prevErrorAction = $ErrorActionPreference
    $ErrorActionPreference = 'Continue'
    try {
        & $FilePath @ArgumentList 2>&1 | ForEach-Object { Write-NativeOutput $_ }
    } finally {
        $ErrorActionPreference = $prevErrorAction
    }

    if (-not $AllowFailure -and $LASTEXITCODE -ne 0) {
        throw "$FilePath fejlede (exit $LASTEXITCODE)."
    }
}

function Sync-GitRepository {
    param(
        [string]$Branch,
        [switch]$Force
    )

    if ($Force) {
        Write-Host "`nForce: synkroniserer med origin/$Branch (lokale Ã¦ndringer kasseres)" -ForegroundColor Magenta

        if (Test-Path (Join-Path $RepoPath '.git\MERGE_HEAD')) {
            Invoke-NativeCommand -Label 'git merge --abort' -FilePath 'git' -ArgumentList @('merge', '--abort')
        }

        if (Test-Path (Join-Path $RepoPath '.git\rebase-merge') -or (Test-Path (Join-Path $RepoPath '.git\rebase-apply'))) {
            Invoke-NativeCommand -Label 'git rebase --abort' -FilePath 'git' -ArgumentList @('rebase', '--abort') -AllowFailure
        }

        Invoke-NativeCommand -Label "git fetch origin $Branch" -FilePath 'git' -ArgumentList @('fetch', 'origin', $Branch)
        Invoke-NativeCommand -Label "git reset --hard origin/$Branch" -FilePath 'git' -ArgumentList @('reset', '--hard', "origin/$Branch")
        return
    }

    Invoke-NativeCommand -Label "[1/3] git pull origin $Branch" -FilePath 'git' -ArgumentList @('pull', 'origin', $Branch)
}

Write-Host "ABA Service deploy" -ForegroundColor Cyan
Write-Host "Mappe: $RepoPath" -ForegroundColor Gray
Set-Location $RepoPath

try {
    Sync-GitRepository -Branch $Branch -Force:$Force
} catch {
    Write-Host "`nGit pull fejlede. PrÃ¸v med -Force for at overskrive lokale Ã¦ndringer:" -ForegroundColor Red
    Write-Host "  .\scripts\deploy.ps1 -Force" -ForegroundColor Yellow
    throw
}

if (-not (Get-Command npm -ErrorAction SilentlyContinue)) {
    throw "npm blev ikke fundet. Installer Node.js (https://nodejs.org) og prøv igen."
}

Invoke-NativeCommand -Label '[2/3] npm install' -FilePath 'npm' -ArgumentList @('install')
Invoke-NativeCommand -Label '[3/3] npm run build (Tailwind CSS)' -FilePath 'npm' -ArgumentList @('run', 'build')

Write-Host "`nFærdig. CSS: public\assets\css\app.css" -ForegroundColor Green
