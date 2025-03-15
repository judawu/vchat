<?php
// db.php
class DB {
    private static $instance = null;
    private $pdo;
    private $logger;

    private function __construct(Logger $logger) {
        $this->logger = $logger;
        $config = require __DIR__ . '/../config/config.php';
        $dsn = "mysql:host={$config['db']['host']};dbname={$config['db']['dbname']};charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];
        try {
            $this->pdo = new PDO($dsn, $config['db']['username'], $config['db']['password'], $options);
        } catch (PDOException $e) {
            $this->logger->error("数据库连接失败: " . $e->getMessage());
            throw $e;
        }
    }

    public static function getInstance(Logger $logger) {
        if (self::$instance === null) {
            self::$instance = new DB($logger);
        }
        return self::$instance;
    }

    public function initTables() {
        try {
            $this->createMessagesTable();
            $this->createRequestsTable();
            $this->createAIContextTable();
        } catch (PDOException $e) {
            $this->logger->error("数据库表初始化失败: " . $e->getMessage());
            throw $e; // 抛出异常以便调试
        }
    }

    public function getPdo() {
        return $this->pdo;
    }

    private function createMessagesTable() {
        $sql = "
            CREATE TABLE IF NOT EXISTS messages (
                id INT AUTO_INCREMENT PRIMARY KEY,
                openid VARCHAR(255) NOT NULL,
                msg_type VARCHAR(50) NOT NULL,
                content TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";
        $this->pdo->exec($sql);
    }
    
    private function createRequestsTable() {
        $sql = "
            CREATE TABLE IF NOT EXISTS requests (
                id INT AUTO_INCREMENT PRIMARY KEY,
                timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                ip VARCHAR(50) NOT NULL,
                location VARCHAR(255) NOT NULL,
                full_url TEXT NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";
        $this->pdo->exec($sql);
    }

    private function createAIContextTable() {
        $sql = "
            CREATE TABLE IF NOT EXISTS aicontext (
                id INT AUTO_INCREMENT PRIMARY KEY,
                conversation TEXT NOT NULL, -- 使用 TEXT 替代 JSON
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";
        $this->pdo->exec($sql);
    }

    public function saveConversation($messages) {
        $sql = "INSERT INTO aicontext (conversation) VALUES (:conversation)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':conversation' => json_encode($messages)]);
        return $this->pdo->lastInsertId();
    }

    public function loadLatestConversation() {
        $sql = "SELECT conversation FROM aicontext ORDER BY created_at DESC LIMIT 1";
        $stmt = $this->pdo->query($sql);
        $result = $stmt->fetchColumn();
        return $result ? json_decode($result, true) : []; // 返回解码后的数组
    }
}
?>