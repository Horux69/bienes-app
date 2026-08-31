@echo off
:: Ejecutar como Administrador (clic derecho -> Ejecutar como administrador)
set HOSTS=%SystemRoot%\System32\drivers\etc\hosts
findstr /C:"bienes_app.test" "%HOSTS%" >nul
if %errorlevel%==0 (
    echo Ya existe la entrada bienes_app.test en hosts.
) else (
    echo.>>"%HOSTS%"
    echo # Laragon - BIENES_APP>>"%HOSTS%"
    echo 192.168.18.5 bienes_app.test>>"%HOSTS%"
    echo Entrada agregada: 192.168.18.5 bienes_app.test
)
pause
