<?php
class Registro
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // Pega o registro ativo (alguém no banheiro)
    public function getAtivo()
    {
        $query = "SELECT r.id, r.id_alunos as id_aluno, r.hora_saida, a.nome 
                  FROM registros_saida r
                  JOIN alunos a ON r.id_alunos = a.id
                  WHERE r.status_alunos = 'EM_ANDAMENTO' LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Pega todos os registros de hoje
    public function getRegistrosHoje()
    {
        $query = "SELECT r.id, r.id_alunos as id_aluno, r.hora_saida, r.hora_retorno, r.duracao_minutos, r.status_alunos, a.nome 
                  FROM registros_saida r
                  JOIN alunos a ON r.id_alunos = a.id
                  WHERE DATE(r.hora_saida) = CURDATE() 
                  ORDER BY r.hora_saida DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Pega toda a fila
    public function getFila()
    {
        $query = "SELECT f.id, f.id_aluno, f.hora_registro_fila, a.nome 
                  FROM fila_banheiro f
                  JOIN alunos a ON f.id_aluno = a.id
                  ORDER BY f.hora_registro_fila ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Registra a saída de um aluno
    public function registrarSaida($id_aluno)
    {
        $query = "INSERT INTO registros_saida (id_alunos, hora_saida, status_alunos) 
                  VALUES (:id_aluno, NOW(), 'EM_ANDAMENTO')";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id_aluno", $id_aluno);
        return $stmt->execute();
    }

    // Registra o aluno na fila
    public function entrarFila($id_aluno)
    {
        $query = "INSERT INTO fila_banheiro (id_aluno, hora_registro_fila) 
                  VALUES (:id_aluno, NOW())";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id_aluno", $id_aluno);
        return $stmt->execute();
    }

    // Finaliza o retorno
    public function registrarRetorno($id_registro)
    {
        // Calcula a duração ao realizar o UPDATE
        $query = "UPDATE registros_saida 
                  SET hora_retorno = NOW(), 
                      duracao_minutos = TIMESTAMPDIFF(MINUTE, hora_saida, NOW()), 
                      status_alunos = 'CONCLUIDO' 
                  WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id_registro);
        return $stmt->execute();
    }

    // Remove o primeiro da fila (para virar o ativo)
    public function proximoDaFila()
    {
        $fila = $this->getFila();
        if (count($fila) > 0) {
            $primeiro = $fila[0];
            // Remove da fila
            $query = "DELETE FROM fila_banheiro WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $primeiro['id']);
            $stmt->execute();

            // Registra a saída dele
            return $this->registrarSaida($primeiro['id_aluno']);
        }
        return false;
    }

    // --- MÉTODOS PARA O DASHBOARD ---

    // Estatísticas gerais
    public function getEstatisticasGeraisHoje()
    {
        $query = "SELECT 
                    COUNT(id) as total_saidas, 
                    COUNT(DISTINCT id_alunos) as total_alunos_distintos, 
                    AVG(duracao_minutos) as tempo_medio, 
                    SUM(duracao_minutos) as tempo_total 
                  FROM registros_saida 
                  WHERE DATE(hora_saida) = CURDATE()";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Todos os alunos que saíram (com infos detalhadas sumárias)
    public function getAlunosSaidasHoje()
    {
        $query = "SELECT a.nome, COUNT(r.id) as qtde_saidas, SUM(IFNULL(r.duracao_minutos, 0)) as tempo_total 
                  FROM registros_saida r
                  JOIN alunos a ON r.id_alunos = a.id
                  WHERE DATE(r.hora_saida) = CURDATE()
                  GROUP BY a.id, a.nome
                  ORDER BY a.nome ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Ranking dos alunos pelo tempo total gasto lá fora (do maior para o menor)
    public function getRankingHoje()
    {
        $query = "SELECT a.nome, COUNT(r.id) as qtde_saidas, SUM(IFNULL(r.duracao_minutos, 0)) as tempo_total 
                  FROM registros_saida r
                  JOIN alunos a ON r.id_alunos = a.id
                  WHERE DATE(r.hora_saida) = CURDATE()
                  GROUP BY a.id, a.nome
                  ORDER BY tempo_total DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>