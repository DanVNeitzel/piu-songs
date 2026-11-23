@echo off
echo ========================================
echo  MOVENDO MUSICA CORROMPIDA
echo ========================================
echo.
echo Musica: 16D4-[Remix] Prime Time
echo Destino: piu-songs\_CORRUPTED\
echo.

REM Criar pasta para músicas corrompidas
if not exist "_CORRUPTED" mkdir "_CORRUPTED"

REM Mover a música problemática
move "Songs\XX\16D4-[Remix] Prime Time" "_CORRUPTED\16D4-[Remix] Prime Time"

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ========================================
    echo  SUCESSO!
    echo ========================================
    echo.
    echo A musica foi movida para: _CORRUPTED\
    echo.
    echo Proximos passos:
    echo 1. Execute: http://localhost/piu-songs/generate_songs_json.php
    echo 2. Copie o songs.json gerado para piuweb_phoenix\piured-engine\
    echo 3. Recarregue a aplicacao (Ctrl+Shift+R)
    echo.
) else (
    echo.
    echo ========================================
    echo  ERRO!
    echo ========================================
    echo.
    echo Nao foi possivel mover a pasta.
    echo Verifique se o caminho esta correto.
    echo.
)

pause
