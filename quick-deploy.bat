@echo off
echo ========================================
echo Food Menu Management System - Quick Deploy
echo Repository: https://github.com/Mzsp7/food-menu-management-system.git
echo ========================================
echo.

REM Check if git is installed
git --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ Git is not installed
    echo Please install Git from https://git-scm.com/
    pause
    exit /b 1
)

echo ✅ Git is available
echo.

REM Check if we're in a git repository
if not exist ".git" (
    echo 🔧 Initializing Git repository...
    git init
    echo.
)

REM Add your specific remote
echo 🔗 Setting up GitHub remote...
git remote remove origin 2>nul
git remote add origin https://github.com/Mzsp7/food-menu-management-system.git
echo.

REM Add all files
echo 📁 Adding files to Git...
git add .
echo.

REM Commit changes
echo 💾 Committing changes...
set /p commit_message="Enter commit message (or press Enter for default): "
if "%commit_message%"=="" set commit_message=Deploy Food Menu Management System
git commit -m "%commit_message%"
echo.

REM Push to GitHub
echo 🚀 Pushing to GitHub...
git branch -M main
git push -u origin main
echo.

echo ========================================
echo ✅ Successfully pushed to GitHub!
echo ========================================
echo.
echo Next steps:
echo 1. Go to https://vercel.com
echo 2. Sign in with GitHub
echo 3. Click "New Project"
echo 4. Import: Mzsp7/food-menu-management-system
echo 5. Click "Deploy"
echo.
echo Your live URL will be:
echo https://food-menu-management-system-mzsp7.vercel.app
echo.
echo Opening Vercel for you...
start https://vercel.com/new
echo.
pause
