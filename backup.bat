@echo off
REM Script para ejecutar backup de Laravel desde Windows Task Scheduler
REM Proyecto: Laboratorio - Sistema de Calidad

cd /d C:\laragon\www\laboratorio

REM Ejecutar el backup
C:\laragon\bin\php\php-8.3.21-Win32-vs16-x64\php.exe artisan backup:run

REM Opcional: Limpiar backups antiguos (mantener solo últimos 7 días)
C:\laragon\bin\php\php-8.3.21-Win32-vs16-x64\php.exe artisan backup:clean

REM Log de ejecución (opcional)
echo Backup ejecutado: %date% %time% >> C:\laragon\www\laboratorio\storage\logs\backup-log.txt
