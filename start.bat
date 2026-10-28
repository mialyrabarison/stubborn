@echo off
cd /d "%~dp0"

echo ========================================
echo STUBBORN - TESTS AUTOMATISES
echo ========================================

php bin/phpunit

if errorlevel 1 (
    echo ERREUR : Les tests ont echoue.
    pause
    exit /b 1
)

echo.
echo ========================================
echo STUBBORN - DEMARRAGE DU SERVEUR
echo ========================================

symfony local:server:start

pause