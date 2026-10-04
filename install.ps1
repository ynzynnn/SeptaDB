# ==============================================================================
#  NexusDB - Windows Local Development Installer / Setup Script
#  Run via PowerShell: powershell -ExecutionPolicy Bypass -File .\install.ps1
# ==============================================================================

Write-Host "====================================================================" -ForegroundColor Cyan
Write-Host "  NexusDB Panel - Windows Local Quick Installer & Setup             " -ForegroundColor Cyan
Write-Host "====================================================================" -ForegroundColor Cyan

# 1. Detect Laragon / Local PHP
$laragonPhp = "C:\laragon\bin\php\php-8.3.26-Win32-vs16-x64"
if (Test-Path $laragonPhp) {
    $env:Path = "$laragonPhp;C:\laragon\bin;C:\laragon\bin\composer;" + $env:Path
    Write-Host "[INFO] Detected Laragon PHP 8.3 environment." -ForegroundColor Green
}

# Check PHP
try {
    $phpVer = php -r "echo PHP_VERSION;"
    Write-Host "[SUCCESS] PHP Version: $phpVer" -ForegroundColor Green
} catch {
    Write-Host "[ERROR] PHP not found in PATH. Please install PHP 8.2+ or run Laragon." -ForegroundColor Red
    exit 1
}

# Check Composer
try {
    composer --version | Out-Null
    Write-Host "[SUCCESS] Composer detected." -ForegroundColor Green
} catch {
    Write-Host "[ERROR] Composer not found." -ForegroundColor Red
    exit 1
}

# Check Node.js
try {
    $nodeVer = node -v
    Write-Host "[SUCCESS] Node.js Version: $nodeVer" -ForegroundColor Green
} catch {
    Write-Host "[ERROR] Node.js not found." -ForegroundColor Red
    exit 1
}

$rootDir = Split-Path -Parent $MyInvocation.MyCommand.Path

# 2. Setup Server
Write-Host "`n[STEP 1/3] Setting up Backend Server..." -ForegroundColor Yellow
Set-Location "$rootDir\server"

if (-not (Test-Path ".env")) {
    Copy-Item ".env.example" ".env"
    Write-Host "[INFO] Created server/.env from template." -ForegroundColor Cyan
}

composer install --no-interaction
php artisan key:generate --force

Write-Host "[INFO] Running database migrations..." -ForegroundColor Cyan
try {
    php artisan migrate --force
    php artisan db:seed --force
} catch {
    Write-Host "[WARNING] Ensure MySQL (Laragon/XAMPP) is running on port 3306." -ForegroundColor Yellow
}

# 3. Setup Client
Write-Host "`n[STEP 2/3] Setting up Frontend Client..." -ForegroundColor Yellow
Set-Location "$rootDir\client"

if (-not (Test-Path ".env")) {
    Copy-Item ".env.example" ".env"
    Write-Host "[INFO] Created client/.env from template." -ForegroundColor Cyan
}

npm install
npm run build

# 4. Summary & Run Commands
Set-Location "$rootDir"
Write-Host "`n====================================================================" -ForegroundColor Green
Write-Host "  NexusDB Local Installation Complete!                              " -ForegroundColor Green
Write-Host "====================================================================" -ForegroundColor Green
Write-Host "To start the development servers locally, run:" -ForegroundColor White
Write-Host " Terminal 1 (Backend) : cd server && php artisan serve" -ForegroundColor Yellow
Write-Host " Terminal 2 (Frontend): cd client && npm run dev" -ForegroundColor Yellow
Write-Host "`nDefault Admin Login : admin / admin123" -ForegroundColor Cyan
Write-Host "Default User Login  : johndoe / user123" -ForegroundColor Cyan
Write-Host "====================================================================" -ForegroundColor Green
