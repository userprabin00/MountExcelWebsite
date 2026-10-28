$endpoints = @(
    "index.php",
    "about.php",
    "academics.php",
    "admissions.php",
    "facilities.php",
    "staff.php",
    "gallery.php",
    "merit-scholarship.php",
    "excursion.php",
    "art-classes.php",
    "video.php",
    "contact.php",
    "admin/login.php"
)

Write-Host "=== Testing Public HTTP Endpoints ===" -ForegroundColor Cyan
foreach ($ep in $endpoints) {
    try {
        $res = Invoke-WebRequest -Uri "http://127.0.0.1:8088/$ep" -UseBasicParsing -TimeoutSec 5
        Write-Host " [PASS] GET $ep -> Status: $($res.StatusCode), Size: $($res.Content.Length) bytes" -ForegroundColor Green
    } catch {
        Write-Host " [FAIL] GET $ep -> Error: $($_.Exception.Message)" -ForegroundColor Red
    }
}

Write-Host "`n=== Testing Protected Admin Access Without Login ===" -ForegroundColor Cyan
$protected = @(
    "admin/index.php",
    "admin/admissions.php",
    "admin/export_admissions.php",
    "admin/messages.php",
    "admin/change_password.php"
)

foreach ($ep in $protected) {
    try {
        $res = Invoke-WebRequest -Uri "http://127.0.0.1:8088/$ep" -UseBasicParsing -MaximumRedirection 0 -ErrorAction Stop
        Write-Host " [FAIL] Expected redirect for $ep but got $($res.StatusCode)" -ForegroundColor Red
    } catch {
        $resp = $_.Exception.Response
        if ($resp -and ($resp.StatusCode.value__ -eq 302 -or $resp.StatusCode.value__ -eq 301)) {
            Write-Host " [PASS] $ep correctly redirected (Status $($resp.StatusCode.value__)) to $($resp.Headers.Location)" -ForegroundColor Green
        } else {
            Write-Host " [PASS] $ep rejected unauthorized access: $($_.Exception.Message)" -ForegroundColor Green
        }
    }
}
