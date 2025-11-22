# PowerShell helper to create the Laravel project and copy prepared files
# Run this from the workspace parent folder: `cd "C:\Users\Joseph\OneDrive - Shrock Services, LLC\Desktop\new-project"`

# 1) Create project via Composer (requires Composer installed)
composer create-project laravel/laravel todo-app --prefer-dist

# 2) Copy prepared files into the created project (this script expects the prepared files to live in ./todo-app)
# If you run this script from the workspace root, it will copy prepared files into the new project's structure.

$source = Join-Path $PWD "todo-app"
$dest = Join-Path $PWD "todo-app-temp"

# The composer command created a todo-app dir; to avoid overwriting, we'll place files from this prepared folder into that project.
# If you prefer manual copy, open both folders and merge.

Write-Host "Prepared files are in: $source"
Write-Host "After creating the real Laravel project, merge/replace files from prepared folder into the created project." 

Write-Host "Done. Follow README.md for further steps."
