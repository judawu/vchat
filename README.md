# VChat 项目介绍

## 项目简介

VChat 是一个基于 PHP 和 HTML 的聊天应用程序，旨在提供用户友好的聊天体验。
[vhat网站](https://vchat.juda.monster/ "vchat网站")
 [![聊天演示](https://github.com/judawu/vchat/blob/main/png/website.PNG)](https://vchat.juda.monster)
## 功能特点

- **AI 聊天仪表板**：提供与 AI 的交互界面，增强用户体验。[AI 聊天仪表板](https://vchat.juda.monster/AIChatDashboard.php)
- **数据库操作**：包含数据库操作功能，支持用户登录和数据管理。
- **Markdown 转换**：支持将 Markdown 格式转换为 HTML，方便内容展示。[Markdown 转换](https://vchat.juda.monster/MarkdownHTMLconverter.php)
- **天气仪表板**：集成天气信息，提供实时天气更新。[天气仪表板](https://vchat.juda.monster/WeatherDashboard.php)  
- **微信功能**：包含与微信相关的功能模块，支持微信操作和草稿管理。
[![聊天演示](https://github.com/judawu/vchat/blob/main/png/Weixin_chat.png)](https://vchat.juda.monster/WeixinDemo.php)
## 文件结构

- `AIChatDashboard.php`：AI 聊天仪表板功能。
- `DatabaseAction.php`：数据库操作功能。
- `DatabaseLogin.php`：数据库登录功能。
- `Databasedashboard.php`：数据库仪表板功能。
- `MarkdownHTMLconverter.php`：Markdown 转 HTML 转换功能。
- `WeatherDashboard.php`：天气仪表板功能。
- `WeixinDemo.php`：微信功能示例。
- `WeixinDraftOperation.php`：微信草稿操作功能。
- `WeixinFunction.php`：微信功能实现。
- `composer.json`：项目依赖配置文件。
- `index.html`：项目主页。
- `log_viewer.php`：日志查看功能。
- `weixin.php`：微信主功能文件。
###  src 目录下文件功能

| 文件名                  | 可能的功能描述                                                                                           |
|-------------------------|--------------------------------------------------------------------------------------------------------|
| **access.php**          | 微信公众号accesstoken功能：<br>- 微信公众号API接口的各种调用，包括草稿发布，Media上传等 |
| **commonAPI.php**       | 通用API函数调用：<br>- 主要是一个访问各家AI 公司API的通用函数和Tools公用API函数 |
| **wechatMsgCrypt.php**  | 微信消息加密组件：<br>- 实现微信服务器消息加解密协议和处理微信消息的签名验证<br> |
| **db.php**              | 数据库连接与基础操作：<br>- 配置MySQL数据库连接参数，建表和查询 |
| **db_delete_data.php**  | 数据清理脚本：<br>- 执行MySQL数据库批量删除操作 |
| **db_update_data.php**  | 数据更新脚本：<br>- 处理MySQL数据变更记录功能 |
| **handle.php**          | 中央请求处理器：<br>- 处理微信公众号被动消息的响应，主要处理XML格式消息 |
| **logger.php**          | 日志记录系统：<br>- 实现日志记录 |
| **message_handler.php** | 消息处理核心：<br>- 处理微信公众号被动消息的响应,主要处理json消息 |
| **receive.php**         | 消息接收网关：<br>-  处理微信公众号被动消息的接收功能 |
| **reply.php**           | 消息回复生成器：<br>- 处理微信公众号被动消息的回复功能 |

---
###  config 目录下文件功能
config只包括了一个config.php,用于进行AI API的桥接，和数据库访问密码以及微信公众号的appid和密码的保存。
## 技术栈

- **后端**：PHP
- **前端**：HTML

## 安装与使用
# vchat 应用部署指南

本指南将引导您在 Linux VPS 或云托管主机上部署 vchat 应用程序。

## 部署步骤
###  手动部署
1.  **购买 VPS 或云托管主机 (Linux)**
2.  **购买您的域名，并在 Cloudflare 上将域名指向您的 VPS 的 IP 地址。**
3.  **安装宝塔面板英文版 (aaPanel)。**
    参考：[https://www.aapanel.com/](https://www.aapanel.com/)
    
5.  **登录 aaPanel 后安装 Nginx, PHP 8.4 和 MySQL。**
    只需创建名为 `vchat` 的数据库，无需创建表。PHP 代码在运行时会自动创建表。
    ![建站演示](https://github.com/judawu/vchat/blob/main/png/aaPanel1.PNG)  
    ![建站演示](https://github.com/judawu/vchat/blob/main/png/aaPanel2.PNG)
7.  **建立网站，并配置网站的根目录。**
    您可以修改提供的 `nginx.conf` 文件并将其内容复制到您的网站配置中。 
    ![建站演示](https://github.com/judawu/vchat/blob/main/png/aaPanel3.PNG)
    ![建站演示](https://github.com/judawu/vchat/blob/main/png/aaPanel4.PNG)
8.  **申请 SSL 安全证书。** 您可以在 aaPanel 中轻松申请和配置 Let's Encrypt 等免费 SSL 证书。
    ![建站演示](https://github.com/judawu/vchat/blob/main/png/aaPanel5.PNG)
9.  **将您的网站文件拷贝到网站的根目录。**
    ![建站演示](https://github.com/judawu/vchat/blob/main/png/aaPanel6.PNG)
10.  **在命令行中进入网站根目录，执行 `composer install` 进行依赖部署。**

###  一键脚本docker部署 

  1. 首先下载build.sh后运行bash build.sh执行一键docker 部署（部署不包括nginx，nginx需要单独安装）
     ![一键部署](https://github.com/judawu/vchat/blob/main/png/aaPanel6.PNG)
  2.  修改nginx，参考网站的nginx。conf进行nginx服务器的设置并重启

## 补充内容：aaPanel 宝塔面板命令行

### Management/面板管理

* **停止：** `service bt stop`
* **启动：** `service bt start`
* **重启：** `service bt restart`
* **卸载：** `service bt stop && chkconfig --del bt && rm -f /etc/init.d/bt && rm -rf /www/server/panel`
* **查看目前面板端口号：** `cat /www/server/panel/data/port.pl`
* **更改面板端口号为 8881 (CentOS 6)：**
    ```bash
    echo '8881' > /www/server/panel/data/port.pl && service bt restart
    iptables -I INPUT -p tcp -m state --state NEW -m tcp --dport 8881 -j ACCEPT
    service iptables save
    service iptables restart
    ```
* **更改面板端口号为 8881 (CentOS 7)：**
    ```bash
    echo '8881' > /www/server/panel/data/port.pl && service bt restart
    firewall-cmd --permanent --zone=public --add-port=8881/tcp
    firewall-cmd --reload
    ```
* **更改 MySQL root 的密码为 123456：** `cd /www/server/panel && python tools.py root 123456`
* **修改面板登录密码为 123456：** `cd /www/server/panel && python tools.py panel 123456`
* **站点配置位置：** `/www/server/panel/vhost`
* **删除控制面板的绑定域：** `rm -f /www/server/panel/data/domain.conf`
* **清除登录限制：** `rm -f /www/server/panel/data/*.login`
* **查看控制面板授权 IP：** `cat /www/server/panel/data/limitip.conf`
* **停止访问限制：** `rm -f /www/server/panel/data/limitip.conf`
* **查看权限域：** `cat /www/server/panel/data/domain.conf`
* **关闭控制面板 SSL：** `rm -f /www/server/panel/data/ssl.pl && /etc/init.d/bt restart`
* **查看控制面板错误日志：** `cat /tmp/panelBoot`
* **查看数据库错误日志：** `cat /www/server/data/*.err`

### Site Configuration (nginx)

* **站点配置目录 (nginx)：** `/www/server/panel/vhost/nginx`
* **站点默认目录：** `/www/wwwroot`
* **数据库备份目录：** `/www/backup/database`
* **站点备份目录：** `/www/backup/site`
* **站点日志：** `/www/wwwlogs`

### Nginx

* **nginx 安装目录：** `/www/server/nginx`
* **启动：** `service nginx start`
* **停止：** `service nginx stop`
* **重启：** `service nginx restart`
* **重新加载：** `service nginx reload`
* **nginx 配置：** `/www/server/nginx/conf/nginx.conf`

### MySQL

* **mysql 安装目录：** `/www/server/mysql`
* **phpmyadmin 安装目录：** `/www/server/phpmyadmin`
* **数据存储目录：** `/www/server/data`
* **启动：** `service mysqld start`
* **停止：** `service mysqld stop`
* **重启：** `service mysqld restart`
* **重新加载：** `service mysqld reload`
* **mysql 配置：** `/etc/my.cnf`

#### 创建一个数据库：

```sql
mysql -u root -p
CREATE DATABASE vchat;
exit
USE vchat;
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    password VARCHAR(100) NOT NULL
);
INSERT INTO users (username, password)
VALUES ("用户名","密码");
SELECT username FROM users WHERE username="用户名";
GRANT ALL PRIVILEGES ON *.* TO 'root'@'IP地址' IDENTIFIED BY '你的密码';
FLUSH PRIVILEGES;
INSERT INTO users (username, password) VALUES (:username, :password) WHERE NOT EXISTS(SELECT username FROM users WHERE username = :username);
```
#### 其他sql操作：
```sql
ALTER TABLE users ADD COLUMN user_id INT NOT NULL;
DELETE FROM users WHERE username="admin'";
UPDATE users SET user_id=7 WHERE username='777';
```
#### 查看表
```sql
USE vchat;
SHOW TABLES;
DESCRIBE messages;
mysql -u root -p vchat
SELECT * FROM messages LIMIT 10;
SELECT * FROM messages ORDER BY id DESC;
DELETE FROM messages;
```


### PHP
* **PHP 安装目录：** ： /www/server/php
* **启动** (请根据 PHP 版本进行修改，例如 service php-fpm-84 start)： service php-fpm-{52|53|54|55|56|70|71|72|73|74|80|81} start
* **停止** (请根据 PHP 版本进行修改，例如 service php-fpm-84 stop)： service php-fpm-{52|53|54|55|56|70|71|72|73|74|80|81} stop
* **重启** (请根据 PHP 版本修改，例如 service php-fpm-84 restart)： service php-fpm-{52|53|54|55|56|70|71|72|73|74|80|81} restart
* **重新加载** (请根据 PHP 版本进行修改，例如 service php-fpm-84 reload)： service php-fpm-{52|53|54|55|56|70|71|72|73|74|80|81} reload

### Composer 的命令
* composer install
* composer update
* composer require <package>
* composer dump-autoload
* composer diagnose
* composer self-update
composer search <term>
composer depends <package>
composer suggests <package>3


# 关注我
  ![微信公众号](https://github.com/judawu/vchat/blob/main/png/wwechat.PNG)
