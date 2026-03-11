<?php
session_start();
header('Content-Type: application/json');

if (isset($_SESSION['professor_logado']) && $_SESSION['professor_logado'] === true) {
    echo json_encode(['status' => 'logado']);
} else {
    echo json_encode(['status' => 'nao_logado']);
}
?>
