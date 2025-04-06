@echo off
setlocal

:: Define the root project folder name
set ProjectFolder=simple-pos

echo Creating project structure for %ProjectFolder%...

:: Create root folder
md "%ProjectFolder%" 2>nul
if errorlevel 1 (
    echo Failed to create root folder: %ProjectFolder%
    goto End
)

:: Create main level files
echo. > "%ProjectFolder%\index.php"
echo. > "%ProjectFolder%\config.php"

:: Create /data folder and files
md "%ProjectFolder%\data" 2>nul
echo. > "%ProjectFolder%\data\products.json"
echo. > "%ProjectFolder%\data\sales.json"
echo Deny from all > "%ProjectFolder%\data\.htaccess" REM Add basic protection if using Apache

:: Create /modules folder structure and files
md "%ProjectFolder%\modules" 2>nul
md "%ProjectFolder%\modules\products" 2>nul
md "%ProjectFolder%\modules\sales" 2>nul
md "%ProjectFolder%\modules\reports" 2>nul
md "%ProjectFolder%\modules\core" 2>nul

echo. > "%ProjectFolder%\modules\products\add_product.php"
echo. > "%ProjectFolder%\modules\products\edit_product.php"
echo. > "%ProjectFolder%\modules\products\product_functions.php"

echo. > "%ProjectFolder%\modules\sales\process_sale.php"
echo. > "%ProjectFolder%\modules\sales\sale_functions.php"

echo. > "%ProjectFolder%\modules\reports\report_functions.php"

echo. > "%ProjectFolder%\modules\core\data_handling.php"
echo. > "%ProjectFolder%\modules\core\helpers.php"

:: Create /templates folder and files
md "%ProjectFolder%\templates" 2>nul
echo. > "%ProjectFolder%\templates\header.php"
echo. > "%ProjectFolder%\templates\footer.php"
echo. > "%ProjectFolder%\templates\product_form.php"
echo. > "%ProjectFolder%\templates\product_list.php"
echo. > "%ProjectFolder%\templates\sales_interface.php"
echo. > "%ProjectFolder%\templates\daily_report.php"
echo. > "%ProjectFolder%\templates\receipt.php"

:: Create /assets folder structure and files
md "%ProjectFolder%\assets" 2>nul
md "%ProjectFolder%\assets\css" 2>nul
md "%ProjectFolder%\assets\js" 2>nul
echo. > "%ProjectFolder%\assets\js\main.js"

echo.
echo Project structure '%ProjectFolder%' created successfully!

:End
endlocal
pause
