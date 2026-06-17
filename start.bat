@echo off
cls
title DevPanelServer
echo Starting DevPanel Server...
echo --------------------------------------

:: Start the node server in the background under this window's title
start /b "" node devpanel-server.mjs

echo --------------------------------------
echo Server is running!
echo --------------------------------------
set /p "="="Press [ENTER] to stop the server..."

:: Target and close the node instance we just started
taskkill /f /fi "WINDOWTITLE eq DevPanelServer*" /im node.exe >nul 2>&1

echo.
echo Server stopped successfully.
pause
