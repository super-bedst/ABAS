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

$ErrorActionPreference = 'Stop'

Write-Host "ABA Service deploy" -ForegroundColor Cyan
Write-Host "Mappe: $RepoPath" -ForegroundColor Gray
Set-Location $RepoPath

Write-Host "`n[1/3] git pull origin $Branch" -ForegroundColor Yellow
git pull origin $Branch

if (-not (Get-Command npm -ErrorAction SilentlyContinue)) {
    throw "npm blev ikke fundet. Installer Node.js (https://nodejs.org) og prøv igen."
}

Write-Host "`n[2/3] npm install" -ForegroundColor Yellow
npm install

Write-Host "`n[3/3] npm run build (Tailwind CSS)" -ForegroundColor Yellow
npm run build

Write-Host "`nFærdig. CSS: public\assets\css\app.css" -ForegroundColor Green
