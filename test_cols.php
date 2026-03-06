<?php
require_once 'config/database.php';

try {
    $out = [];
    $stmt = $conn->query("DESCRIBE registros_saida");
    $out['registros_saida'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $conn->query("DESCRIBE fila_banheiro");
    $out['fila_banheiro'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $conn->query("DESCRIBE alunos");
    $out['alunos'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    file_put_contents('schema.json', json_encode($out, JSON_PRETTY_PRINT));
    echo "Salvo em schema.json";
} catch (Exception $e) {
    echo $e->getMessage();
}
?>
