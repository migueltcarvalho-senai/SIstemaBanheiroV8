<?php
// Configuração do Banco de Dados
ini_set('display_errors', '0'); // Evita que Warnings do PHP quebrem a estrutura JSON das respostas (Unexpected token '<')

define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'sistema_banheiro');
define('DB_PORT', '3307');

class Database
{
    private static $instance = null;
    private $conn;

    private function __construct()
    {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $this->conn = new PDO($dsn, DB_USER, DB_PASS, $options);
        }
        catch (PDOException $e) {
            die(json_encode([
                "status" => "error",
                "message" => "Erro de conexão com o banco de dados: " . $e->getMessage()
            ]));
        }
    }

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new Database();
        }
        return self::$instance->conn;
    }
}
?>
