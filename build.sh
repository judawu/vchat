cd /usr/local/nsx/www/wwwroot/
git clone https://github.com/judawu/vchat.git
sudo mkdir -p /usr/local/nsx/www/wwwroot/mysql/
sudo chown -R 1001:1001 /usr/local/nsx/www/wwwroot/mysql/  # MySQL 默认 UID/GID 是 1001（或检查镜像文档）
sudo chmod -R 755 /usr/local/nsx/www/wwwroot/mysql/

cd /usr/local/nsx/www/wwwroot/vchat/
docker build -t vchat-php .
docker compose up -d
docker ps  # 确认 vchat-php 和 vchat-db 运行
docker compose logs db  # 检查 MySQL 初始化
docker compose logs php  # 检查 PHP 启动




