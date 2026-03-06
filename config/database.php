<?php
// Configurações do Banco de Dados
define("DB_HOST", "127.0.0.1");
define("DB_NAME", "sistema_banheiro"); // Certifique-se de que é este o nome do seu banco 
define("DB_USER", "root");
define("DB_PASS", "");
define("DB_PORT", "3307");

// Conexão
try {
    $conn = new PDO("mysql:host=". DB_HOST.";port=". DB_PORT .";dbname=". DB_NAME.";charset=utf8mb4", DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} 
catch (PDOException $e) {
    die("Erro na conexão com o banco de dados: ". $e->getMessage());
}

if(session_status() === PHP_SESSION_NONE){
    session_start();
}
?>
