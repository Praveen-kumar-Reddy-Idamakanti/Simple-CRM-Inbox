1. I analyzed the three files which are given in the project-requirements folder
2. Read requirements.md file and understand the Goals and deliverables
3. I choose to take poller.py as it is a python file and i had worked on python before
4. read the poller.py file and understand the code it is doing mainly three things\
    a. fetching pending messages from mock server
    b. forward webhook payload to target Laravel /api/webhook
    c. polling the mock server for every 5 seconds (default), i think this is little slow but okay to go
5. planned with Claude to implement the Laravel backend into 10 phases  
6. started the first phase with creating the project folders for backend + frontend
7. installed Xamp, composer using this command
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    php -r "if (hash_file('sha384', 'composer-setup.php') === 'c8b085408188070d5f52bcfe4ecfbee5f727afa458b2573b8eaaf77b3419b0bf2768dc67c86944da1544f06fa544fd47') { echo 'Installer verified'.PHP_EOL; } else { echo 'Installer corrupt'.PHP_EOL; unlink('composer-setup.php'); exit(1); }"
    php composer-setup.php
    php -r "unlink('composer-setup.php');" 
8. ran this command in backend folder(php composer.phar create-project laravel/laravel crm-inbox)
9. integrated react in resources/js/app.jsx
10. installed MongoDB PHP extension and added to php.ini
11. configured .env file to use MongoDB
12. configured database.php file to use MongoDB 



