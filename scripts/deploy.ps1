# ABAS - hent seneste kode fra GitHub og byg Tailwind CSS
# Kor fra projektmappen eller angiv -RepoPath
#
# Eksempler:
#   .\scripts\deploy.ps1
#   .\scripts\deploy.ps1 -Force
#   .\scripts\deploy.ps1 -SkipNpm
#
# -Force:   Afbryd uafsluttet merge og overskriv lokale aendringer med origin/master
# -SkipNpm: Spring npm over (brug CSS fra git ? hurtig deploy af PHP)

param(
    [string]$RepoPath = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path,
    [string]$Branch = 'master',
    [switch]$Force,
    [switch]$SkipNpm
)

function Write-NativeOutput {
    process {
        if ($_ -is [System.Management.Automation.ErrorRecord]) {
            Write-Host $_.ToString()
        } elseif ("$_" -ne '') {
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

    return $LASTEXITCODE
}

function Invoke-NpmCommand {
    param(
        [string]$Label,
        [string]$NpmArgs,
        [switch]$AllowFailure
    )

    Write-Host "`n$Label" -ForegroundColor Yellow

    $prevErrorAction = $ErrorActionPreference
    $ErrorActionPreference = 'Continue'
    $exitCode = 0
    try {
        # npm.cmd via cmd.exe giver mere palidelige exit codes i Windows PowerShell 5.1
        cmd.exe /c "npm $NpmArgs" 2>&1 | ForEach-Object { Write-NativeOutput $_ }
        $exitCode = $LASTEXITCODE
    } finally {
        $ErrorActionPreference = $prevErrorAction
    }

    if (-not $AllowFailure -and $exitCode -ne 0) {
        throw "npm fejlede (exit $exitCode)."
    }

    return $exitCode
}

function Test-TailwindReady {
    return (Test-Path (Join-Path $RepoPath 'node_modules\.bin\tailwindcss.cmd')) -or
           (Test-Path (Join-Path $RepoPath 'node_modules\.bin\tailwindcss'))
}

function Install-NpmDependencies {
    $lockFile = Join-Path $RepoPath 'package-lock.json'
    $args = if (Test-Path $lockFile) {
        'ci --no-audit --no-fund'
    } else {
        'install --no-audit --no-fund'
    }

    $exitCode = Invoke-NpmCommand -Label "[2/3] npm $args" -NpmArgs $args -AllowFailure
    if ($exitCode -eq 0) {
        return $true
    }

    Write-Host "`nnpm install fejlede (exit $exitCode)." -ForegroundColor Red
    if ($exitCode -eq -4048 -or $exitCode -eq 4048) {
        Write-Host "Typisk arsag: fil-las i node_modules (antivirus, IDE eller WAMP)." -ForegroundColor Yellow
        Write-Host "Prov: luk editor, slet node_modules, kor deploy igen." -ForegroundColor Yellow
        Write-Host "Eller brug: .\scripts\deploy.ps1 -SkipNpm" -ForegroundColor Yellow
    }

    if (Test-TailwindReady) {
        Write-Host "node_modules findes ? prover Tailwind build alligevel." -ForegroundColor Yellow
        return $true
    }

    if (Test-Path (Join-Path $RepoPath 'public\assets\css\app.css')) {
        Write-Host "public\assets\css\app.css findes fra git ? deploy kan fortsaette uden npm." -ForegroundColor Yellow
        Write-Host "Brug -SkipNpm naeste gang hvis npm bliver ved med at fejle." -ForegroundColor Yellow
        return $false
    }

    throw "npm fejlede (exit $exitCode) og Tailwind er ikke tilgaengelig."
}

function Sync-GitRepository {
    param(
        [string]$Branch,
        [switch]$Force
    )

    $envBackups = @{}
    foreach ($envFile in @('env.local', '.env.local')) {
        $envPath = Join-Path $RepoPath $envFile
        if (Test-Path $envPath) {
            $envBackups[$envFile] = Get-Content -Path $envPath -Raw -Encoding UTF8
            Write-Host "Bevarer lokalt: $envFile" -ForegroundColor Gray
        }
    }

    if ($Force) {
        Write-Host "`nForce: synkroniserer med origin/$Branch (lokale aendringer kasseres)" -ForegroundColor Magenta

        if (Test-Path (Join-Path $RepoPath '.git\MERGE_HEAD')) {
            Invoke-NativeCommand -Label 'git merge --abort' -FilePath 'git' -ArgumentList @('merge', '--abort')
        }

        if ((Test-Path (Join-Path $RepoPath '.git\rebase-merge')) -or (Test-Path (Join-Path $RepoPath '.git\rebase-apply'))) {
            Invoke-NativeCommand -Label 'git rebase --abort' -FilePath 'git' -ArgumentList @('rebase', '--abort') -AllowFailure
        }

        Invoke-NativeCommand -Label "[1/3] git fetch origin $Branch" -FilePath 'git' -ArgumentList @('fetch', 'origin', $Branch)
        Invoke-NativeCommand -Label "git reset --hard origin/$Branch" -FilePath 'git' -ArgumentList @('reset', '--hard', "origin/$Branch")

        foreach ($envFile in $envBackups.Keys) {
            $envPath = Join-Path $RepoPath $envFile
            Set-Content -Path $envPath -Value $envBackups[$envFile] -Encoding UTF8 -NoNewline
            Write-Host "Gendannet lokalt: $envFile" -ForegroundColor Gray
        }
        return
    }

    Invoke-NativeCommand -Label "[1/3] git pull origin $Branch" -FilePath 'git' -ArgumentList @('pull', 'origin', $Branch)

    foreach ($envFile in $envBackups.Keys) {
        $envPath = Join-Path $RepoPath $envFile
        Set-Content -Path $envPath -Value $envBackups[$envFile] -Encoding UTF8 -NoNewline
        Write-Host "Gendannet lokalt: $envFile" -ForegroundColor Gray
    }
}

Write-Host "ABA Service deploy" -ForegroundColor Cyan
Write-Host "Mappe: $RepoPath" -ForegroundColor Gray
Set-Location $RepoPath

try {
    Sync-GitRepository -Branch $Branch -Force:$Force
} catch {
    Write-Host "`nGit pull fejlede. Prov med -Force:" -ForegroundColor Red
    Write-Host "  .\scripts\deploy.ps1 -Force" -ForegroundColor Yellow
    throw
}

if ($SkipNpm) {
    Write-Host "`nSkipNpm: springer npm over (bruger CSS fra git)." -ForegroundColor Gray
    if (-not (Test-Path (Join-Path $RepoPath 'public\assets\css\app.css'))) {
        Write-Host "Advarsel: public\assets\css\app.css mangler!" -ForegroundColor Red
    }
    Write-Host "`nFaerdig (kun git pull)." -ForegroundColor Green
    exit 0
}

if (-not (Get-Command npm -ErrorAction SilentlyContinue)) {
    if (Test-Path (Join-Path $RepoPath 'public\assets\css\app.css')) {
        Write-Host "`nnpm ikke fundet ? bruger CSS fra git." -ForegroundColor Yellow
        Write-Host "Faerdig (uden Tailwind build)." -ForegroundColor Green
        exit 0
    }
    throw "npm blev ikke fundet. Installer Node.js (https://nodejs.org) eller brug -SkipNpm."
}

$canBuild = Install-NpmDependencies
if ($canBuild -or (Test-TailwindReady)) {
    try {
        Invoke-NpmCommand -Label '[3/3] npm run build (Tailwind CSS)' -NpmArgs 'run build'
    } catch {
        if (Test-Path (Join-Path $RepoPath 'public\assets\css\app.css')) {
            Write-Host "Build fejlede, men eksisterende app.css fra git bruges." -ForegroundColor Yellow
        } else {
            throw
        }
    }
}

Write-Host "`nFaerdig. CSS: public\assets\css\app.css" -ForegroundColor Green
