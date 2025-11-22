# todo-app (prepared files)

This folder contains prepared Laravel files for a simple To-Do app. The recommended workflow is:

1. Open PowerShell and change to your workspace:

```powershell
cd "C:\Users\Joseph\OneDrive - Shrock Services, LLC\Desktop\new-project"
```

2. Create a new Laravel project (this will download vendor files):

```powershell
composer create-project laravel/laravel todo-app --prefer-dist
```

3. Copy these prepared files into the created `todo-app` project (overwrite or merge). You can also manually paste file contents into the corresponding paths.

4. In the `todo-app` directory run:

```powershell
cd todo-app
copy .env.example .env
php artisan key:generate
New-Item -ItemType File -Path database\database.sqlite -Force
# Edit .env to set DB_CONNECTION=sqlite and DB_DATABASE=database/database.sqlite
php artisan migrate --seed
npm install
npm run dev
php artisan serve --host=127.0.0.1 --port=8000
```

5. Open `http://127.0.0.1:8000/todos` to use the app.

If you want me to run equivalent commands locally I need the tool environment to execute shell commands. Otherwise, run the above in your PowerShell.
