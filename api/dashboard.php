<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../config/database.php';
include_once '../models/Registro.php';

$registro = new Registro($conn);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $gerais = $registro->getEstatisticasGeraisHoje();
    $alunos = $registro->getAlunosSaidasHoje();
    $ranking = $registro->getRankingHoje();

    echo json_encode(array(
        "estatisticas" => $gerais,
        "alunos" => $alunos,
        "ranking" => $ranking
    ));
}
?>