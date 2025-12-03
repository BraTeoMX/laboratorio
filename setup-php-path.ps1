# Script para agregar PHP y Composer al PATH de Windows
# Ejecutar como Administrador en PowerShell

Write-Host "=== Configuración de PHP PATH para Laragon ===" -ForegroundColor Cyan
Write-Host ""

# Rutas de Laragon
$phpPath = "C:\laragon\bin\php\php-8.3.21-Win32-vs16-x64"
$composerPath = "C:\laragon\bin\composer"

# Obtener PATH actual
$currentPath = [Environment]::GetEnvironmentVariable("Path", [EnvironmentVariableTarget]::Machine)

# Función para agregar al PATH
function Add-ToPath {
    param($pathToAdd)
    
    if ($currentPath -notlike "*$pathToAdd*") {
        $newPath = "$currentPath;$pathToAdd"
        [Environment]::SetEnvironmentVariable("Path", $newPath, [EnvironmentVariableTarget]::Machine)
        Write-Host "✓ Agregado: $pathToAdd" -ForegroundColor Green
        return $true
    } else {
        Write-Host "○ Ya existe: $pathToAdd" -ForegroundColor Yellow
        return $false
    }
}

# Agregar rutas
$phpAdded = Add-ToPath -pathToAdd $phpPath
$composerAdded = Add-ToPath -pathToAdd $composerPath

Write-Host ""
if ($phpAdded -or $composerAdded) {
    Write-Host "=== PATH actualizado correctamente ===" -ForegroundColor Green
    Write-Host ""
    Write-Host "IMPORTANTE: Debes REINICIAR PowerShell para que los cambios surtan efecto" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "Después de reiniciar, verifica con:" -ForegroundColor Cyan
    Write-Host "  php --version" -ForegroundColor White
    Write-Host "  composer --version" -ForegroundColor White
} else {
    Write-Host "=== No se requieren cambios ===" -ForegroundColor Green
}
