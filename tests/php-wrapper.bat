@echo off
REM Wrapper that invokes Local's PHP 8.4 with the test ini file.
REM Used by wp-phpunit's install.php subprocess launcher.
set "PHPRC=D:\i-Downloads wordpress\i-downloads\tests"
"%APPDATA%\Local\lightning-services\php-8.4.4+2\bin\win64\php.exe" -c "D:\i-Downloads wordpress\i-downloads\tests\php.ini" %*
