<?php
header("Content-Type: application/json; charset=UTF-8");
require_once '../config/database.php';
require_once '../models/Aluno.php';

$db = Database::getInstance();
$aluno = new Aluno($db);

$numero = isset($_GET['numero']) ? intval($_GET['numero']) : 0;
$id_turma = isset($_GET['id_turma']) ? intval($_GET['id_turma']) : 0;

if ($numero > 0 && $id_turma > 0) {
    $dados = $aluno->getAlunoByNumero($numero, $id_turma);
    if ($dados) {
        echo json_encode(["status" => "success", "aluno" => $dados]);
    }
    else {
        echo json_encode(["status" => "error", "message" => "Aluno não encontrado nesta turma"]);
    }
}
else {
    echo json_encode(["status" => "error", "message" => "Número ou turma inválida"]);
}
?>
