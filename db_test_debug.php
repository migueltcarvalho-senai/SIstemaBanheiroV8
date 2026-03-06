<?php
require_once 'config/database.php';

try {
    $db = Database::getInstance();
    echo "<h1>Debug de Conexão e Metadados SQL</h1>";

    $stmt = $db->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (count($tables) > 0) {
        foreach ($tables as $table) {
            echo "<h3>Tabela: $table</h3>";
            $colStmt = $db->query("SHOW COLUMNS FROM `$table`");
            $columns = $colStmt->fetchAll(PDO::FETCH_ASSOC);
            echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
            foreach ($columns as $col) {
                echo "<tr>";
                echo "<td>{$col['Field']}</td>";
                echo "<td>{$col['Type']}</td>";
                echo "<td>{$col['Null']}</td>";
                echo "<td>{$col['Key']}</td>";
                echo "<td>{$col['Default']}</td>";
                echo "<td>{$col['Extra']}</td>";
                echo "</tr>";
            }
            echo "</table><br/>";
        }
    } else {
        echo "<p>Nenhuma tabela encontrada no DB.</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color:red;'>Erro PDO: " . $e->getMessage() . "</p>";
}
?>
