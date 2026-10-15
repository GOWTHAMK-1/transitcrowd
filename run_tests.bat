@echo off
title Run Master Automated Test Suite
cd /d "%~dp0"
echo Running Public Transport Crowd Predictor Test Suite...
C:\xampp\php\php.exe tests\test_all.php
pause
