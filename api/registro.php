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
    // Retorna o status atual do painel principal (quem está fora, fila e registros limitados de hoje)
    $ativo = $registro->getAtivo();
    $fila = $registro->getFila();
    $hoje = $registro->getRegistrosHoje();
    
    echo json_encode([
        "status" => "success", 
        "ativo" => $ativo, 
        "fila" => $fila, 
        "registros" => $hoje
    ]);

} elseif ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"));
    
    if (!isset($data->id_alunos) || empty($data->id_alunos)) {
        echo json_encode(["status" => "error", "message" => "ID do aluno não informado."]);
        exit;
    }
    
    $id_alunos = $data->id_alunos;
    
    // 1. Verifica se o aluno existe
    $dadosAluno = $aluno->getAlunoById($id_alunos);
    if (!$dadosAluno) {
        echo json_encode(["status" => "error", "message" => "Aluno não encontrado no banco."]);
        exit;
    }
    
    $ativo = $registro->getAtivo();
    
    // 2. Se o ID digitado for o mesmo do aluno que já está no banheiro (voltou)
    if ($ativo && $ativo['id_alunos'] == $id_alunos) {
        if ($registro->registrarRetorno($id_alunos)) {
            echo json_encode(["status" => "success", "message" => "Retorno registrado com sucesso. Próximo da fila (se houver) foi chamado."]);
        } else {
            echo json_encode(["status" => "error", "message" => "Erro ao registrar o retorno."]);
        }
        exit;
    }
    
    // 3. Se o banheiro estiver ocupado (alguém diferente), entra na fila
    if ($ativo) {
        if ($registro->entrarFila($id_alunos)) {
            echo json_encode(["status" => "success", "message" => "Banheiro Ocupado. Aluno adicionado à fila de espera."]);
        } else {
            echo json_encode(["status" => "error", "message" => "Este aluno já está na fila de espera."]);
        }
        exit;
    }
    
    // 4. Se o banheiro estiver livre, registra a saída imediatamente
    if ($registro->registrarSaida($id_alunos)) {
         echo json_encode(["status" => "success", "message" => "Saída registrada com sucesso."]);
    } else {
         echo json_encode(["status" => "error", "message" => "Erro desconhecido ao registrar saída."]);
    }
}
?>
