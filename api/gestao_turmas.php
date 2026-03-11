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
    // Listar turmas
    try {
        $stmt = $db->query("SELECT id, nome FROM turmas ORDER BY nome ASC");
        $turmas = $stmt->fetchAll();
        echo json_encode(['status' => 'success', 'turmas' => $turmas]);
    }
    catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Erro ao buscar turmas: ' . $e->getMessage()]);
    }
}
elseif ($method === 'POST') {
    // Criar turma
    $inputJSON = file_get_contents('php://input');
    $input = json_decode($inputJSON, true);

    if (isset($input['nome']) && !empty(trim($input['nome']))) {
        try {
            $stmt = $db->prepare("INSERT INTO turmas (nome) VALUES (:nome)");
            $stmt->execute(['nome' => trim($input['nome'])]);
            echo json_encode(['status' => 'success', 'message' => 'Turma criada com sucesso.', 'id' => $db->lastInsertId()]);
        }
        catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => 'Erro ao criar turma: ' . $e->getMessage()]);
        }
    }
    else {
        echo json_encode(['status' => 'error', 'message' => 'Nome da turma inválido.']);
    }
}
elseif ($method === 'DELETE') {
    // Deletar turma
    $inputJSON = file_get_contents('php://input');
    $input = json_decode($inputJSON, true);

    if (isset($input['id'])) {
        try {
            $idTurma = intval($input['id']);

            // CASCADE DELETE manual: remove primeiro os registros filhos
            // 1. Deleta registros de fila_banheiro relacionados à turma
            $stmt = $db->prepare("DELETE FROM fila_banheiro WHERE id_turma = :id_turma");
            $stmt->execute(['id_turma' => $idTurma]);

            // 2. Deleta registros_saida relacionados à turma
            $stmt = $db->prepare("DELETE FROM registros_saida WHERE id_turma = :id_turma");
            $stmt->execute(['id_turma' => $idTurma]);

            // 3. Deleta todos os alunos da turma
            $stmt = $db->prepare("DELETE FROM alunos WHERE id_turma = :id_turma");
            $stmt->execute(['id_turma' => $idTurma]);

            // 4. Deleta a turma em si
            $stmt = $db->prepare("DELETE FROM turmas WHERE id = :id");
            $stmt->execute(['id' => $idTurma]);

            // Reseta posições no ID
            $db->exec("ALTER TABLE turmas AUTO_INCREMENT = 1");
            $db->exec("ALTER TABLE alunos AUTO_INCREMENT = 1");

            echo json_encode(['status' => 'success', 'message' => 'Turma e todos os dados associados deletados com sucesso.']);
        }
        catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => 'Erro ao deletar turma: ' . $e->getMessage()]);
        }
    }
    else {
        echo json_encode(['status' => 'error', 'message' => 'ID da turma não fornecido.']);
    }
}
else {
    echo json_encode(['status' => 'error', 'message' => 'Método inválido.']);
}
?>
