# Professional Push Script for FACTORY_SYSTEM
# Run this script to stage, commit, and push your changes.

Write-Host "Starting professional commit and push process..." -ForegroundColor Cyan

# 1. Initialize Git if needed
if (!(Test-Path .git)) {
    Write-Host "Initializing Git repository..."
    git init
}

# 2. Set remote if not set
$remotes = git remote
if ($remotes -notcontains "origin") {
    Write-Host "Adding remote origin..."
    git remote add origin https://github.com/Amerkhorsheed/FACTORY_SYSTEM.git
} else {
    Write-Host "Remote origin already exists."
    git remote set-url origin https://github.com/Amerkhorsheed/FACTORY_SYSTEM.git
}

# 3. Stage changes
Write-Host "Staging all changes..."
git add .

# 4. Professional Commit (Only if there are changes)
$status = git status --porcelain
if ($status) {
    $commitMsg = "feat: initialize enterprise management docs and Laravel scaffold"
    $commitDesc = "Integrated 6 core management files (AGENT.md, PROGRESS.md, etc.) and bootstrapped Laravel 11 project structure with enterprise standards."

    Write-Host "Committing changes..."
    git commit -m "$commitMsg" -m "$commitDesc"
} else {
    Write-Host "No new changes to commit."
}

# 5. Ensure branch is 'main'
Write-Host "Setting branch to main..."
git branch -M main

# 6. Push
Write-Host "Pushing to GitHub..."
git push -u origin main

Write-Host "Push complete! Your repository is now up to date." -ForegroundColor Green
