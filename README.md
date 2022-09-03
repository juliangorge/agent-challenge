Agent Challenge

Run using:
docker-compose up -d

POST
docker exec -i agent_agent_1 php index.php -u <username> -p <password>

GET
docker exec -i agent_agent_1 php index.php -u <username> -p <password> -e
