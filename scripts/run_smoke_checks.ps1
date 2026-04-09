param(
    [string]$BaseUrl = "http://127.0.0.1:8090"
)

$targets = @(
    "/pages/main.php",
    "/pages/tournaments.html",
    "/pages/blog.html",
    "/pages/gallery.html",
    "/pages/about.html",
    "/services/get_session_context.php",
    "/services/get_tournaments.php",
    "/services/get_blogs.php?page=1&pageSize=5",
    "/services/gallery_get.php"
)

$failed = $false

foreach ($target in $targets) {
    $url = "$BaseUrl$target"

    try {
        $response = Invoke-WebRequest -Uri $url -UseBasicParsing -TimeoutSec 20
        Write-Host "[OK] $url -> $($response.StatusCode)"
    } catch {
        Write-Host "[FAIL] $url -> $($_.Exception.Message)"
        $failed = $true
    }
}

if ($failed) {
    exit 1
}

Write-Host "Smoke checks passed."
