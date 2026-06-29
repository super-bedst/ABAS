# Hent portal_migration_update.sql fra portalservren (UTF-8 bevares via pscp).
# Kræver VPN/adgang til 10.181.140.105 og PuTTY (plink/pscp).
#
# Eksempler:
#   .\scripts\fetch_portal_update_sql.ps1
#   .\scripts\fetch_portal_update_sql.ps1 -InstallersOnly

param(
    [switch]$InstallersOnly
)

$ErrorActionPreference = 'Stop'

$HostKey = 'SHA256:CNCbP+00Il+emIVOHHFfbb1b7K7a+vTYxuwefKaMJkw'
$Server = 'administrator@10.181.140.105'
$Password = 'Trekantbrand2016!'
$RepoRoot = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$LocalScript = Join-Path $RepoRoot 'scripts\generate_portal_import_sql.php'
$RemoteScript = 'generate_portal_import_sql.php'
$RemoteSql = '/home/administrator/portal_migration_update.sql'
$LocalSql = if ($InstallersOnly) {
    Join-Path $RepoRoot 'Database\imports\portal_migration_update_installers.sql'
} else {
    Join-Path $RepoRoot 'Database\imports\portal_migration_update.sql'
}

$phpArgs = if ($InstallersOnly) {
    "php ~/$RemoteScript --installers-only --output=$RemoteSql"
} else {
    "php ~/$RemoteScript --output=$RemoteSql"
}

Write-Host "Upload script..." -ForegroundColor Cyan
& pscp -pw $Password -batch -hostkey $HostKey $LocalScript "${Server}:${RemoteScript}"

Write-Host "Generer SQL på server..." -ForegroundColor Cyan
& plink -ssh $Server -pw $Password -batch -hostkey $HostKey $phpArgs

Write-Host "Hent SQL (binær/UTF-8)..." -ForegroundColor Cyan
New-Item -ItemType Directory -Force -Path (Split-Path $LocalSql) | Out-Null
& pscp -pw $Password -batch -hostkey $HostKey "${Server}:${RemoteSql}" $LocalSql

$bytes = (Get-Item $LocalSql).Length
Write-Host "Gemt: $LocalSql ($bytes bytes)" -ForegroundColor Green
