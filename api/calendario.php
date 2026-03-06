<?php
header("Content-Type: application/json; charset=UTF-8");
require_once '../config/database.php';
require_once '../models/Registro.php';

$db = Database::getInstance();
$registro = new Registro($db);

$data = isset($_GET['data']) ? $_GET['data'] : date('Y-m-d');
$registros = $registro->getRegistrosPorData($data);

echo json_encode([
    "status" => "success",
    "data" => $data,
    "registros" => $registros
]);
?>
