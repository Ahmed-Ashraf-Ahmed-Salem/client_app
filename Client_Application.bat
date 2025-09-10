@echo off
cd /d "C:\Users\ahmed_ashraf\Downloads\clientapplication\target"

echo Starting the Spring Boot application...
start "SpringBootApp" java -jar clientapplication.jar

:: Wait for the server to fully start (adjust delay if needed)
timeout /t 10 >nul

pause
