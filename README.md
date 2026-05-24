# CityReport - A Municipal Problem Reporting Platform

## Installation and operation instructions

This app is completely automated through a Docker. It has been configured so as it handles permission issues and compatibility issues (Windows & Linux).

### Requirements:

Docker and docker compose installed into your system.

#### Steps:

1. Open your terminal (or Command Prompt/PowerShell) in the root directory of the project (where the docker-compose.yml file is located).

2. Run the following command:
   docker compose up --build

3. Open your browser and navigate to: http://localhost:8080

4. Exposed Ports:
   When the containers are running, the application uses the following ports on your host machine: 

   # Port 8080: Web Application (Apache/PHP)
   ## Port 8082: My SQL database


5. Stopping the application: 
   To stop the application press ctrl + c in the running terminal. Otherwise you can open a new terminal in the project folder and run the command: docker compose down. 
   If you want a whole reset of the application please run the command docker compose down -v. 
   (Warning: This deletes the database and everythinf in it.)

6. Technical Notes: 
    5.1 Database Automation: Tables are automatically created during the  first server startup via the create_db.php file.

    5.2 File management: The uploads directory is automatically created by the container with full read and write permissions. This way the photos can be saved or deleted across all platforms (Windows,Linux etc).

7. Important note: 
   Please wait a few seconds (about 10-15) for the database to be fully initialized and set up and for the rights.sh script to have finished executing. This ensures everything is ready and prevents any connection errors.

Author: Anastasis Nikolou.
