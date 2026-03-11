<?php
header("Content-Type: application/json; charset=UTF-8");
require_once '../config/database.php';
require_once '../models/Registro.php';

$db = Database::getInstance();
$registro = new Registro($db);

$id_turma = isset($_GET['id_turma']) ? intval($_GET['id_turma']) : null;

$estatisticas = $registro->getEstatisticasHoje($id_turma);
$ranking = $registro->getRankingHoje($id_turma);

echo json_encode([
    "status" => "success",
    "estatisticas" => $estatisticas,
    "ranking" => $ranking
]);
?>
