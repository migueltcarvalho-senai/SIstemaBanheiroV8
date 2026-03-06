<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

define("DB_HOST", "localhost");
define("DB_NAME", "sistema_banheiro");
define("DB_USER", "root");
define("DB_PASS", "");
define("DB_PORT", "3307");

echo "Tentando conectar em " . DB_HOST . ":" . DB_PORT . " no banco " . DB_NAME . "...\n";

try {
    $conn = new PDO("mysql:host=". DB_HOST.";port=". DB_PORT .";dbname=". DB_NAME.";charset=utf8mb4", DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Sucesso: Conexao estabelecida!";
} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage();
}
?>
