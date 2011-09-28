echo off
REM paz is a simple packaging and deployment tool for PHP projects on Windows Azure.
REM See http://github.com/blobaugh/paz

set PAZDIR=%~dp0


php "%PAZDIR%\paz.php" %*
