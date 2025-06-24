@echo off
echo ========================================
echo Food Menu Management System - Deployment
echo ========================================
echo.

REM Check if git is installed
git --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ Git is not installed or not in PATH
    echo Please install Git from https://git-scm.com/
    echo.
    pause
    exit /b 1
)

echo ✅ Git is available
echo.

REM Initialize git repository if not already done
if not exist ".git" (
    echo 🔧 Initializing Git repository...
    git init
    echo.
)

REM Add all files
echo 📁 Adding files to Git...
git add .
echo.

REM Check if there are changes to commit
git diff --cached --quiet
if %errorlevel% equ 0 (
    echo ℹ️ No changes to commit
) else (
    echo 💾 Committing changes...
    set /p commit_message="Enter commit message (or press Enter for default): "
    if "%commit_message%"=="" set commit_message=Update Food Menu Management System
    git commit -m "%commit_message%"
    echo.
)

REM Check if remote origin exists
git remote get-url origin >nul 2>&1
if %errorlevel% neq 0 (
    echo 🔗 Setting up GitHub remote...
    set /p github_repo="Enter your GitHub repository URL: "
    git remote add origin %github_repo%
    echo.
)

REM Push to GitHub
echo 🚀 Pushing to GitHub...
git branch -M main
git push -u origin main
echo.

REM Check if Vercel CLI is installed
vercel --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ⚠️ Vercel CLI not found
    echo.
    echo You can deploy in two ways:
    echo 1. Install Vercel CLI: npm i -g vercel
    echo 2. Deploy via Vercel website: https://vercel.com
    echo.
    echo Opening Vercel website...
    start https://vercel.com/new
) else (
    echo ✅ Vercel CLI is available
    echo.
    echo 🚀 Deploying to Vercel...
    vercel --prod
)

echo.
echo ========================================
echo Deployment Complete!
echo ========================================
echo.
echo Your project is now available at:
echo - GitHub: Check your repository
echo - Vercel: Check your Vercel dashboard
echo.
echo Next steps:
echo 1. Update README.md with your actual URLs
echo 2. Set up environment variables in Vercel (if using PHP)
echo 3. Configure custom domain (optional)
echo.
pause
