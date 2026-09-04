param(
    [string]$PhpExe = "php",
    [string]$Root = (Resolve-Path (Join-Path $PSScriptRoot "..")).Path
)

if (-not (Get-Command $PhpExe -ErrorAction SilentlyContinue)) {
    Write-Error "PHP executable not found: $PhpExe. Pass -PhpExe with an explicit path if PHP is not on PATH."
    exit 1
}

$files = Get-ChildItem -Path $Root -Recurse -Filter *.php -File | Sort-Object FullName
$failed = $false

foreach ($file in $files) {
    & $PhpExe -l $file.FullName
    if ($LASTEXITCODE -ne 0) {
        $failed = $true
    }
}

if ($failed) {
    exit 1
}

Write-Host "PHP lint passed for $($files.Count) files."
