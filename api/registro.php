<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Inclui o DB que exporta a variável $conn conectada
include_once '../config/database.php';
include_once '../models/Aluno.php';
include_once '../models/Registro.php';

// Injetando a conexão global $conn nos módulos (via construtor)
$aluno = new Aluno($conn);
$registro = new Registro($conn);

// Pega os dados do POST
$data = json_decode(file_get_contents("php://input"));

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(!empty($data->id_aluno)) {
        $id_aluno = $data->id_aluno;
        
        // Verifica se aluno existe
        if($aluno->getAlunoById($id_aluno)) {
            // Existe um ativo?
            $ativo = $registro->getAtivo();

            // Se existe alguém no banheiro
            if($ativo) {
                // É a mesma pessoa que está no banheiro retornando?
                if($ativo['id_aluno'] == $id_aluno) {
                    // Retorna do banheiro
                    $registro->registrarRetorno($ativo['id']);
                    
                    // Alguem na fila? Coloca o próximo no status ativo
                    $registro->proximoDaFila();

                    echo json_encode(array("mensagem" => "Retorno registrado com sucesso.", "status" => "success"));
                } else {
                    // É outra pessoa querendo sair, coloca na fila
                    // Verifica se já está na fila
                    $fila = $registro->getFila();
                    $jaNaFila = false;
                    foreach($fila as $f) {
                        if($f['id_aluno'] == $id_aluno) {
                            $jaNaFila = true;
                            break;
                        }
                    }
                    
                    if(!$jaNaFila) {
                        $registro->entrarFila($id_aluno);
                        echo json_encode(array("mensagem" => "Banheiro ocupado. Aluno adicionado à fila.", "status" => "success_fila"));
                    } else {
                        echo json_encode(array("mensagem" => "Aluno já está na fila.", "status" => "error"));
                    }
                }
            } else {
                // Ninguém no banheiro, pode ir
                $registro->registrarSaida($id_aluno);
                echo json_encode(array("mensagem" => "Saída registrada com sucesso.", "status" => "success"));
            }
        } else {
            echo json_encode(array("mensagem" => "Aluno não encontrado.", "status" => "error"));
        }
    } else {
        echo json_encode(array("mensagem" => "ID do aluno não informado.", "status" => "error"));
    }
} 
// Requisição GET para buscar os dados atuais para a tela
else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $ativo = $registro->getAtivo();
    $listaHoje = $registro->getRegistrosHoje();
    $fila = $registro->getFila();

    echo json_encode(array(
        "ativo" => $ativo,
        "registros_hoje" => $listaHoje,
        "fila" => $fila
    ));
}
?>
