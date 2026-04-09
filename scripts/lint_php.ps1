param(
    [string]$PhpExe = "C:\Users\Ali\xampp\php\php.exe",
    [string]$Root = (Resolve-Path (Join-Path $PSScriptRoot "..")).Path
)

if (-not (Test-Path $PhpExe)) {
    Write-Error "PHP executable not found at $PhpExe"
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
