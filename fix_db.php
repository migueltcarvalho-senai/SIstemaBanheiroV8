<?php
require_once 'config/database.php';

try {
    $db = Database::getInstance();
    echo "<h1>Atualização do Banco de Dados</h1>";

    // 1. Garantir ENUM no status_alunos
    $query1 = "ALTER TABLE registros_saida MODIFY COLUMN status_alunos ENUM('EM_ANDAMENTO', 'CONCLUIDO') NOT NULL DEFAULT 'EM_ANDAMENTO'";
    $db->exec($query1);
    echo "<p>OK: Coluna status_alunos atualizada para ENUM correto.</p>";

    // 2. Limpar registros órfãos ou inválidos (sem aluno)
    $query2 = "DELETE r FROM registros_saida r LEFT JOIN alunos a ON r.id_alunos = a.id_alunos WHERE a.id_alunos IS NULL";
    $affected2 = $db->exec($query2);
    echo "<p>OK: $affected2 registros órfãos removidos de registros_saida.</p>";

    // 3. Limpar fila inválida
    $query3 = "DELETE f FROM fila_banheiro f LEFT JOIN alunos a ON f.id_alunos = a.id_alunos WHERE a.id_alunos IS NULL";
    $affected3 = $db->exec($query3);
    echo "<p>OK: $affected3 registros órfãos removidos de fila_banheiro.</p>";

    echo "<h3>Banco de dados atualizado/revisado com sucesso.</h3>";

} catch (PDOException $e) {
    echo "<h1>Erro Crítico</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>
