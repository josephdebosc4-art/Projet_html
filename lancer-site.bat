@echo off
setlocal
cd /d "%~dp0"

if not exist "C:\xampp\php\php.exe" (
    echo PHP est introuvable dans C:\xampp\php.
    echo Installe XAMPP ou modifie le chemin dans ce fichier.
    pause
    exit /b 1
)

set "APP_DB_HOST=127.0.0.1"
set "APP_DB_PORT=3306"
set "APP_DB_NAME=suivi_apprentissage"
set "APP_DB_USER=root"
set "APP_DB_PASSWORD="
set "APP_BASE_URL="

echo.
echo Le site va demarrer sur http://127.0.0.1:8000
echo Verifie que MySQL est demarre dans le panneau XAMPP.
echo Pour arreter le site, utilise Ctrl+C puis ferme cette fenetre.
echo.

"C:\xampp\php\php.exe" -d upload_max_filesize=5M -d post_max_size=32M -d max_file_uploads=10 -S 127.0.0.1:8000 router.php
pause
