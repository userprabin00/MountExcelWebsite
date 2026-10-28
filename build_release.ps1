$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $MyInvocation.MyCommand.Path
$releaseDir = Join-Path $root 'release'
$stage = Join-Path ([IO.Path]::GetTempPath()) ("MountExcelWebsite-release-" + [guid]::NewGuid().ToString('N'))
$archive = Join-Path $releaseDir 'MountExcelWebsite-deploy.zip'

New-Item -ItemType Directory -Path $releaseDir -Force | Out-Null
New-Item -ItemType Directory -Path $stage -Force | Out-Null
try {
    Get-ChildItem -LiteralPath $root -File -Recurse -Force | ForEach-Object {
        $relative = $_.FullName.Substring($root.Length).TrimStart('\', '/').Replace('\', '/')
        $parts = $relative -split '[\\/]'
        if ($parts -contains '.git' -or $parts -contains 'tests' -or $parts -contains 'release') { return }
        if ($relative -in @('.env', '.gitignore', 'README.md', 'database/seed.php', 'database/seed_content.php', 'images/background1.jpg')) { return }
        if ($relative -match '\.zip$') { return }
        if ($relative -match '\.md$' -and $relative -ne 'DEPLOYMENT.md') { return }
        if ($relative -match '\.sql$' -and $relative -ne 'database/schema.sql') { return }
        if ($relative -match '^(backups|logs)/' -and $relative -notmatch '^(backups|logs)/(\.gitkeep|\.htaccess)$') { return }
        if ($relative -match '^uploads/(gallery|staff)/' -and $relative -notmatch '^uploads/(gallery|staff)/(\.gitkeep|\.htaccess)$') { return }
        $destination = Join-Path $stage ($relative.Replace('/', [IO.Path]::DirectorySeparatorChar))
        New-Item -ItemType Directory -Path (Split-Path -Parent $destination) -Force | Out-Null
        Copy-Item -LiteralPath $_.FullName -Destination $destination -Force
    }
    foreach ($directory in @('backups', 'logs', 'uploads/gallery', 'uploads/staff')) {
        $path = Join-Path $stage $directory
        New-Item -ItemType Directory -Path $path -Force | Out-Null
        New-Item -ItemType File -Path (Join-Path $path '.gitkeep') -Force | Out-Null
    }
    foreach ($directory in @('uploads/gallery', 'uploads/staff')) {
        $guard = Join-Path $stage (Join-Path $directory '.htaccess')
        if (-not (Test-Path -LiteralPath $guard)) {
            Set-Content -LiteralPath $guard -Value "Require all denied`nDeny from all" -Encoding ASCII
        }
    }
    if (Test-Path -LiteralPath $archive) { Remove-Item -LiteralPath $archive -Force }
    Add-Type -AssemblyName System.IO.Compression.FileSystem
    [IO.Compression.ZipFile]::CreateFromDirectory($stage, $archive, [IO.Compression.CompressionLevel]::Optimal, $false)
    Write-Output "Created $archive"
}
finally {
    Remove-Item -LiteralPath $stage -Recurse -Force -ErrorAction SilentlyContinue
}
