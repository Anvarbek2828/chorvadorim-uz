<#
.SYNOPSIS
    git diff bo'yicha o'zgargan fayllarni FTPS orqali serverga yuklaydi.

.DESCRIPTION
    From..To oralig'ida qo'shilgan/o'zgargan/nomi o'zgargan (A, M, R) fayllar
    belgilangan tartibda yuklanadi. O'chirilgan (D) fayllar serverdan
    o'chirilmaydi, faqat ro'yxatda ko'rsatiladi.

    Login/parol faqat netrc fayldan curl.exe orqali olinadi; skript bu faylni
    o'qimaydi va hech qayerga chiqarmaydi.

.EXAMPLE
    .\scripts\ftp-deploy.ps1 -From 6eabd0c -To bc7a6a5 -DryRun
#>
[CmdletBinding()]
param(
    [Parameter(Mandatory = $true)][string]$From,
    [string]$To = 'HEAD',
    [switch]$DryRun,
    [string]$NetrcFile = 'E:\ftp\chorvadorim.netrc',
    [string]$FtpHost = '45.135.181.128'
)

$ErrorActionPreference = 'Stop'

# Server sertifikati ishonchsiz (self-signed), shuning uchun FTPS --insecure bilan.
$CurlMode = @('--ssl-reqd', '--insecure')

$repoRoot = (git rev-parse --show-toplevel).Trim()
if ($LASTEXITCODE -ne 0) { throw 'Git repozitoriy topilmadi.' }
Set-Location $repoRoot

foreach ($rev in @($From, $To)) {
    git rev-parse --verify --quiet "$rev^{commit}" | Out-Null
    if ($LASTEXITCODE -ne 0) { throw "Commit topilmadi: $rev" }
}

$excludePrefixes = @('tests/*', 'database/sql/*', 'storage/*', 'vendor/*', 'node_modules/*', 'scripts/*')
$excludeLeaves = @('*.md', '.env*', 'phpunit.xml', 'package*.json', 'composer.*',
    'vite.config.*', 'tailwind.config.*', 'postcss.config.*')

function Test-Excluded([string]$path) {
    foreach ($p in $excludePrefixes) { if ($path -like $p) { return $true } }
    foreach ($seg in $path.Split('/')) { if ($seg -like '.git*') { return $true } }
    $leaf = $path.Split('/')[-1]
    foreach ($p in $excludeLeaves) { if ($leaf -like $p) { return $true } }
    return $false
}

# Indeks = guruh raqami (Get-Group qaytaradigan qiymat).
$groupNames = @(
    $null
    'database/migrations'
    'app/Models, app/Services, app/Support'
    'app/Http/Middleware'
    'qolgan app/'
    'resources/views'
    'lang'
    'public/build (assets)'
    'public/build (manifest.json)'
    'boshqa fayllar'
    'bootstrap/app.php'
    'routes/web.php'
)

function Get-Group([string]$path) {
    if ($path -eq 'bootstrap/app.php') { return 10 }
    if ($path -eq 'routes/web.php') { return 11 }
    if ($path -like 'database/migrations/*') { return 1 }
    if ($path -like 'app/Models/*' -or $path -like 'app/Services/*' -or $path -like 'app/Support/*') { return 2 }
    if ($path -like 'app/Http/Middleware/*') { return 3 }
    if ($path -like 'app/*') { return 4 }
    if ($path -like 'resources/views/*') { return 5 }
    if ($path -like 'lang/*') { return 6 }
    if ($path -like 'public/build/*') {
        if ($path.Split('/')[-1] -eq 'manifest.json') { return 8 }
        return 7
    }
    return 9
}

$upload = @()
$deleted = @()
$excluded = @()
$skipped = @()

$diff = git -c core.quotepath=false diff --name-status -M "$From..$To"
if ($LASTEXITCODE -ne 0) { throw 'git diff bajarilmadi.' }

foreach ($line in $diff) {
    if ([string]::IsNullOrWhiteSpace($line)) { continue }
    $cols = $line.Split("`t")
    $status = $cols[0].Substring(0, 1)
    $path = $cols[-1]

    switch ($status) {
        'D' { $deleted += $path; continue }
        { $_ -in 'A', 'M', 'R' } {
            if (Test-Excluded $path) { $excluded += "$status  $path" }
            else { $upload += [pscustomobject]@{ Status = $status; Path = $path; Group = (Get-Group $path) } }
            continue
        }
        default { $skipped += $line }
    }
}

$upload = @($upload | Sort-Object Group, Path)

Write-Host "Diapazon: $From..$To"
Write-Host "Rejim:    FTPS (--ssl-reqd --insecure) -> ftp://$FtpHost/"
if ($DryRun) { Write-Host 'DRY RUN: hech narsa yuklanmaydi.' -ForegroundColor Yellow }
Write-Host ''

Write-Host "Yuklanadigan fayllar ($($upload.Count)), tartib bilan:" -ForegroundColor Cyan
$n = 0
foreach ($g in 1..($groupNames.Count - 1)) {
    $items = @($upload | Where-Object { $_.Group -eq $g })
    if ($items.Count -eq 0) { continue }
    Write-Host "  [$g] $($groupNames[$g])"
    foreach ($f in $items) {
        $n++
        Write-Host ('    {0,3}. {1}  {2}' -f $n, $f.Status, $f.Path)
    }
}

if ($excluded.Count) {
    Write-Host ''
    Write-Host "Istisno qilingan, yuklanmaydi ($($excluded.Count)):" -ForegroundColor DarkGray
    $excluded | ForEach-Object { Write-Host "    $_" }
}
if ($deleted.Count) {
    Write-Host ''
    Write-Host "O'chirilgan, serverdan O'CHIRILMAYDI ($($deleted.Count)):" -ForegroundColor DarkYellow
    $deleted | ForEach-Object { Write-Host "    D  $_" }
}
if ($skipped.Count) {
    Write-Host ''
    Write-Host "Boshqa statusli, e'tiborsiz qoldirildi ($($skipped.Count)):" -ForegroundColor DarkGray
    $skipped | ForEach-Object { Write-Host "    $_" }
}

if ($DryRun -or $upload.Count -eq 0) { return }

# Yuklash ishchi papkadagi fayllardan bo'ladi, shuning uchun ular To commitiga mos bo'lishi shart.
$headSha = (git rev-parse HEAD).Trim()
$toSha = (git rev-parse "$To^{commit}").Trim()
if ($headSha -ne $toSha) { throw "To ($To) HEAD ga teng emas. Avval 'git checkout $To' qiling." }
$dirty = git status --porcelain -- ($upload | ForEach-Object { $_.Path })
if ($dirty) { throw "Yuklanadigan fayllarda commit qilinmagan o'zgarishlar bor:`n$($dirty -join "`n")" }
if (-not (Test-Path -LiteralPath $NetrcFile)) { throw "Netrc fayl topilmadi: $NetrcFile" }

Write-Host ''
Write-Host 'Yuklash boshlandi...' -ForegroundColor Cyan
$i = 0
foreach ($f in $upload) {
    $i++
    $local = Join-Path $repoRoot $f.Path
    $remote = ($f.Path.Split('/') | ForEach-Object { [Uri]::EscapeDataString($_) }) -join '/'
    $url = "ftp://$FtpHost/$remote"

    curl.exe -sS @CurlMode --netrc-file $NetrcFile --ftp-create-dirs --connect-timeout 30 --max-time 300 -T $local $url
    if ($LASTEXITCODE -ne 0) {
        Write-Host ('  {0,3}/{1}  XATO  {2}  (curl kodi {3})' -f $i, $upload.Count, $f.Path, $LASTEXITCODE) -ForegroundColor Red
        Write-Host "To'xtatildi. Qolgan $($upload.Count - $i) ta fayl (bootstrap/app.php va routes/web.php ham) yuklanmadi." -ForegroundColor Red
        exit 1
    }
    Write-Host ('  {0,3}/{1}  OK    {2}' -f $i, $upload.Count, $f.Path) -ForegroundColor Green
}

Write-Host ''
Write-Host "Tayyor: $($upload.Count) ta fayl yuklandi." -ForegroundColor Green
