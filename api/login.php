<?php
session_start();
header('Content-Type: application/json');

// Get the raw POST data
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

$senha = isset($input['senha']) ? $input['senha'] : '';

if ($senha === '12345678') {
    $_SESSION['professor_logado'] = true;
    echo json_encode(['status' => 'success', 'message' => 'Login efetuado com sucesso.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Senha incorreta.']);
}
?>
