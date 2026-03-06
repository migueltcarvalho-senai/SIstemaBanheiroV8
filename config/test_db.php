<?php
// Script de Diagnóstico de Conexão - test_db.php
require_once 'database.php';

try {
    $db = Database::getInstance();
    echo "<h1>Conexão com o Banco de Dados: SUCESSO!</h1>";
    echo "<p>Configurações utilizadas:</p>";
    echo "<ul>";
    echo "<li>Host: " . DB_HOST . "</li>";
    echo "<li>Porta: " . DB_PORT . "</li>";
    echo "<li>Usuário: " . DB_USER . "</li>";
    echo "<li>Banco: " . DB_NAME . "</li >";
    echo "</ul>";

    // Testar as tabelas
    $stmt = $db->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (count($tables) > 0) {
        echo "<h2>Tabelas Encontradas:</h2><ul>";
        foreach ($tables as $table) {
            echo "<li>$table</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: red;'><strong>Aviso:</strong> Nenhuma tabela encontrada no banco de dados.</p>";
    }

} catch (Exception $e) {
    echo "<h1>Falha na Conexão</h1>";
    echo "<p style='color: red;'>" . $e->getMessage() . "</p>";
}
?>
