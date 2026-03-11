<?php
session_start();
header('Content-Type: application/json');
require_once '../config/database.php';

// Verificar se o professor está logado
if (!isset($_SESSION['professor_logado']) || $_SESSION['professor_logado'] !== true) {
    echo json_encode(['status' => 'error', 'message' => 'Não autorizado.']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$db = Database::getInstance();

if ($method === 'GET') {
    // Listar alunos de uma turma específica
    if (isset($_GET['id_turma'])) {
        try {
            $stmt = $db->prepare("SELECT id, nome, numero_chamada FROM alunos WHERE id_turma = :id_turma ORDER BY numero_chamada ASC");
            $stmt->execute(['id_turma' => $_GET['id_turma']]);
            $alunos = $stmt->fetchAll();
            echo json_encode(['status' => 'success', 'alunos' => $alunos]);
        }
        catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => 'Erro ao buscar alunos: ' . $e->getMessage()]);
        }
    }
    else {
        echo json_encode(['status' => 'error', 'message' => 'ID da turma não fornecido.']);
    }
}
elseif ($method === 'POST') {
    // Criar aluno
    $inputJSON = file_get_contents('php://input');
    $input = json_decode($inputJSON, true);

    if (isset($input['nome']) && !empty(trim($input['nome'])) && isset($input['numero_chamada']) && isset($input['id_turma'])) {
        $nome = trim($input['nome']);
        $numero = intval($input['numero_chamada']);
        $id_turma = intval($input['id_turma']);

        if ($numero < 0) {
            echo json_encode(['status' => 'error', 'message' => 'O número da chamada não pode ser negativo.']);
            exit;
        }

        try {
            // Verificar duplicidade de nome ou número na mesma turma
            $check = $db->prepare("SELECT id FROM alunos WHERE id_turma = :id_turma AND (numero_chamada = :numero OR LOWER(nome) = LOWER(:nome))");
            $check->execute(['id_turma' => $id_turma, 'numero' => $numero, 'nome' => $nome]);
            if ($check->rowCount() > 0) {
                echo json_encode(['status' => 'error', 'message' => 'Já existe um aluno com este nome ou número nesta turma.']);
                exit;
            }

            $stmt = $db->prepare("INSERT INTO alunos (nome, numero_chamada, id_turma) VALUES (:nome, :numero_chamada, :id_turma)");
            $stmt->execute([
                'nome' => $nome,
                'numero_chamada' => $numero,
                'id_turma' => $id_turma
            ]);
            echo json_encode(['status' => 'success', 'message' => 'Aluno criado com sucesso.', 'id' => $db->lastInsertId()]);
        }
        catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => 'Erro ao criar aluno: ' . $e->getMessage()]);
        }
    }
    else {
        echo json_encode(['status' => 'error', 'message' => 'Dados do aluno inválidos.']);
    }
}
elseif ($method === 'DELETE') {
    // Deletar aluno
    $inputJSON = file_get_contents('php://input');
    $input = json_decode($inputJSON, true);

    if (isset($input['id'])) {
        try {
            $idAluno = intval($input['id']);

            // CASCADE DELETE manual: remove registros filhos antes do aluno
            // 1. Remove registros da fila_banheiro vinculados a este aluno
            $stmt = $db->prepare("DELETE FROM fila_banheiro WHERE id_alunos = :id_aluno");
            $stmt->execute(['id_aluno' => $idAluno]);

            // 2. Remove registros_saida vinculados a este aluno
            $stmt = $db->prepare("DELETE FROM registros_saida WHERE id_alunos = :id_aluno");
            $stmt->execute(['id_aluno' => $idAluno]);

            // 3. Remove o aluno em si
            $stmt = $db->prepare("DELETE FROM alunos WHERE id = :id");
            $stmt->execute(['id' => $idAluno]);

            // Reseta posições no ID
            $db->exec("ALTER TABLE alunos AUTO_INCREMENT = 1");

            echo json_encode(['status' => 'success', 'message' => 'Aluno e todos os registros associados deletados com sucesso.']);
        }
        catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => 'Erro ao deletar aluno: ' . $e->getMessage()]);
        }
    }
    else {
        echo json_encode(['status' => 'error', 'message' => 'ID do aluno não fornecido.']);
    }
}
else {
    echo json_encode(['status' => 'error', 'message' => 'Método inválido.']);
}
?>
