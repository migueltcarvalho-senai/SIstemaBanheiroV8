<?php
header("Content-Type: application/json; charset=UTF-8");
require_once '../config/database.php';
require_once '../models/Aluno.php';
require_once '../models/Registro.php';

$db = Database::getInstance();
$aluno = new Aluno($db);
$registro = new Registro($db);

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Lendo o id_turma da query string
    $id_turma = isset($_GET['id_turma']) ? intval($_GET['id_turma']) : null;

    // Retorna o status atual do painel principal
    $ativo = $registro->getAtivo($id_turma);
    $fila = $registro->getFila($id_turma);
    $hoje = $registro->getRegistrosHoje($id_turma);

    echo json_encode([
        "status" => "success",
        "ativo" => $ativo,
        "fila" => $fila,
        "registros" => $hoje
    ]);

}
elseif ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"));

    // Valida os campos obrigatórios
    if (!isset($data->numero_chamada) || !isset($data->id_turma)) {
        echo json_encode(["status" => "error", "message" => "Número da chamada ou turma não informados."]);
        exit;
    }

    $numero_chamada = intval($data->numero_chamada);
    $id_turma = intval($data->id_turma);

    // 1. Verifica se o aluno existe nesta turma com este número
    $dadosAluno = $aluno->getAlunoByNumero($numero_chamada, $id_turma);
    if (!$dadosAluno) {
        echo json_encode(["status" => "error", "message" => "Aluno não encontrado nesta turma com este número."]);
        exit;
    }

    // 2. Verifica se há alguém NO BANHEIRO desta turma
    $ativo = $registro->getAtivo($id_turma);

    // 2a. Se for o MESMO aluno ativo → registra o retorno
    if ($ativo && $ativo['numero_chamada'] == $numero_chamada) {
        if ($registro->registrarRetorno($numero_chamada, $id_turma)) {
            echo json_encode(["status" => "success", "message" => "Retorno registrado com sucesso. Próximo da fila (se houver) foi chamado."]);
        }
        else {
            echo json_encode(["status" => "error", "message" => "Erro ao registrar o retorno."]);
        }
        exit;
    }

    // 2b. Se há outro aluno ativo → entra na fila
    if ($ativo) {
        if ($registro->entrarFila($numero_chamada, $id_turma)) {
            echo json_encode(["status" => "success", "message" => "Banheiro Ocupado. Aluno adicionado à fila de espera."]);
        }
        else {
            echo json_encode(["status" => "error", "message" => "Este aluno já está na fila de espera."]);
        }
        exit;
    }

    // 2c. Banheiro livre → registra a saída imediatamente
    if ($registro->registrarSaida($numero_chamada, $id_turma)) {
        echo json_encode(["status" => "success", "message" => "Saída registrada com sucesso."]);
    }
    else {
        echo json_encode(["status" => "error", "message" => "Erro desconhecido ao registrar saída."]);
    }
}
?>
