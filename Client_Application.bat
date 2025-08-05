@echo off
cd /d "C:\Users\ahmed_ashraf\Downloads\clientapplication\target"

echo Starting the Spring Boot application...
start "SpringBootApp" java -jar clientapplication.jar

:: Wait for the server to fully start (adjust delay if needed)
::echo Waiting for the server to start...
timeout /t 10 >nul

:: Call the API using curl (you can adjust the body as needed)
::echo Sending POST request to the API...
::curl -X POST http://127.0.0.1:1515/api/clientapp/delete
::echo.	 
::echo Done.
pause
