<?php
header("Content-Type: application/json; charset=UTF-8");
require_once '../config/database.php';
require_once '../models/Aluno.php';

$db = Database::getInstance();
$aluno = new Aluno($db);

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id > 0) {
    $dados = $aluno->getAlunoById($id);
    if ($dados) {
        echo json_encode(["status" => "success", "aluno" => $dados]);
    } else {
        echo json_encode(["status" => "error", "message" => "Aluno não encontrado"]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "ID inválido"]);
}
?>
