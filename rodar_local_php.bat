@echo off
title COMARA - Servidor Local de Eleição dos Padrões
echo ==========================================================
echo    COMARA - SISTEMA DE ELEIÇÃO DOS PADRÕES (MILITAR E CIVIL)
echo ==========================================================
echo.
echo Iniciando servidor PHP embutido na porta 8080...
echo Acesse no seu navegador: http://localhost:8080/
echo.
echo Pressione Ctrl+C para finalizar.
echo ==========================================================
php -S localhost:8080 -t public
