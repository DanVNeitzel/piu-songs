@echo off
setlocal
title PIU Songs - Gerar catalogo
set "GENERATION_EXIT=1"
set "PHP_BIN=php.exe"
set "GENERATION_LOGS=%TEMP%\piu-songs-geracao"

pushd "%~dp0"
if errorlevel 1 goto finish

where.exe php.exe >nul 2>nul
if not errorlevel 1 goto php_ready
set "PHP_BIN=%~dp0..\..\php\php.exe"
if exist "%PHP_BIN%" goto php_ready
echo ERRO: PHP nao encontrado. Instale o PHP e adicione-o ao PATH.
goto cleanup

:php_ready
if not exist "generate_songs_json.php" goto missing_scripts
if not exist "generate_difficulty_zone.php" goto missing_scripts
if not exist "base_songs.json" goto missing_scripts
if not exist "channels.json" goto missing_scripts
if not exist "channel_catalog.php" goto missing_scripts
if not exist "Songs\" (
    echo ERRO: A pasta Songs nao foi encontrada.
    goto cleanup
)
if not exist "%GENERATION_LOGS%\" mkdir "%GENERATION_LOGS%"
if not exist "%GENERATION_LOGS%\" (
    echo ERRO: Nao foi possivel criar a pasta de logs.
    goto cleanup
)

echo [1/2] Gerando songs.json...
"%PHP_BIN%" "generate_songs_json.php" >"%GENERATION_LOGS%\catalogo.log" 2>&1
if errorlevel 1 (
    echo ERRO na geracao do catalogo. A geracao de dificuldades foi cancelada.
    echo Consulte: "%GENERATION_LOGS%\catalogo.log"
    goto cleanup
)

echo [2/2] Gerando os indices de dificuldade...
"%PHP_BIN%" "generate_difficulty_zone.php" >"%GENERATION_LOGS%\dificuldades.log" 2>&1
if errorlevel 1 (
    echo ERRO na geracao dos indices de dificuldade.
    echo Consulte: "%GENERATION_LOGS%\dificuldades.log"
    goto cleanup
)

echo.
echo Concluido: songs.json e difficulty-zone atualizados.
echo Recarregue o jogo com Ctrl+F5.
echo Logs: "%GENERATION_LOGS%"
set "GENERATION_EXIT=0"
goto cleanup

:missing_scripts
echo ERRO: Um dos scripts ou arquivos de configuracao nao foi encontrado.
echo Mantenha este BAT na raiz do projeto piu-songs.

:cleanup
popd

:finish
echo.
if /I not "%~1"=="--no-pause" pause
exit /b %GENERATION_EXIT%
