<?php
class Registro
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // Busca aluno que está no banheiro (ativo)
    public function getAtivo($id_turma = null)
    {
        $query = "SELECT r.id, r.numero_chamada, r.id_turma, a.nome, r.hora_saida 
                  FROM registros_saida r
                  JOIN alunos a ON r.numero_chamada = a.numero_chamada AND r.id_turma = a.id_turma
                  WHERE r.status_alunos = 'EM_ANDAMENTO'";

        if ($id_turma) {
            $query .= " AND r.id_turma = :id_turma";
        }
        $query .= " LIMIT 1";

        $stmt = $this->conn->prepare($query);
        if ($id_turma) {
            $stmt->bindParam(":id_turma", $id_turma, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Lista fila de espera
    public function getFila($id_turma = null)
    {
        $query = "SELECT f.id, f.numero_chamada, f.id_turma, a.nome, f.hora_registro_fila as hora_entrada_fila
                  FROM fila_banheiro f
                  JOIN alunos a ON f.numero_chamada = a.numero_chamada AND f.id_turma = a.id_turma";

        if ($id_turma) {
            $query .= " WHERE f.id_turma = :id_turma";
        }
        $query .= " ORDER BY f.hora_registro_fila ASC";

        $stmt = $this->conn->prepare($query);
        if ($id_turma) {
            $stmt->bindParam(":id_turma", $id_turma, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Adiciona aluno à fila
    public function entrarFila($numero_chamada, $id_turma)
    {
        // Verifica se já está na fila
        $query_check = "SELECT id FROM fila_banheiro WHERE numero_chamada = :numero AND id_turma = :id_turma";
        $stmt_check = $this->conn->prepare($query_check);
        $stmt_check->execute([':numero' => $numero_chamada, ':id_turma' => $id_turma]);
        if ($stmt_check->rowCount() > 0)
            return false;

        $query = "INSERT INTO fila_banheiro (numero_chamada, id_turma, hora_registro_fila) VALUES (:numero, :id_turma, NOW())";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':numero' => $numero_chamada, ':id_turma' => $id_turma]);
        return $stmt->rowCount() > 0;
    }

    // Registra a saída (entra no banheiro)
    public function registrarSaida($numero_chamada, $id_turma)
    {
        // Verifica se já existe alguém da MESMA TURMA ativo
        if ($this->getAtivo($id_turma))
            return false;

        $query = "INSERT INTO registros_saida (numero_chamada, id_turma, hora_saida, status_alunos) 
                  VALUES (:numero, :id_turma, NOW(), 'EM_ANDAMENTO')";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':numero' => $numero_chamada, ':id_turma' => $id_turma]);
        return $stmt->rowCount() > 0;
    }

    // Registra o retorno (sai do banheiro) e opcionalmente puxa próximo
    public function registrarRetorno($numero_chamada, $id_turma)
    {
        try {
            $this->conn->beginTransaction();

            $query = "UPDATE registros_saida 
                      SET hora_retorno = NOW(), 
                          duracao_minutos = TIMESTAMPDIFF(MINUTE, hora_saida, NOW()),
                          status_alunos = 'CONCLUIDO' 
                      WHERE numero_chamada = :numero AND id_turma = :id_turma AND status_alunos = 'EM_ANDAMENTO'";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([':numero' => $numero_chamada, ':id_turma' => $id_turma]);

            // Puxar o próximo da fila
            $this->proximoDaFila($id_turma);

            $this->conn->commit();
            return true;
        }
        catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    // Move primeiro da fila para o banheiro
    private function proximoDaFila($id_turma)
    {
        $fila = $this->getFila($id_turma);
        if (count($fila) > 0) {
            $proximo = $fila[0];
            $num_proximo = $proximo['numero_chamada'];
            $id_fila = $proximo['id'];

            // Remove da fila
            $q_del = "DELETE FROM fila_banheiro WHERE id = :id_fila";
            $s_del = $this->conn->prepare($q_del);
            $s_del->bindParam(":id_fila", $id_fila);
            $s_del->execute();

            // Registra saída do próximo
            $q_insert = "INSERT INTO registros_saida (numero_chamada, id_turma, hora_saida, status_alunos) 
                         VALUES (:numero, :id_turma, NOW(), 'EM_ANDAMENTO')";
            $s_insert = $this->conn->prepare($q_insert);
            $s_insert->execute([':numero' => $num_proximo, ':id_turma' => $id_turma]);
        }
    }

    // Retorna todos os registros de hoje
    public function getRegistrosHoje($id_turma = null)
    {
        $query = "SELECT r.id, r.numero_chamada, r.id_turma, a.nome, r.hora_saida, r.hora_retorno, r.duracao_minutos as tempo_gasto, r.status_alunos 
                  FROM registros_saida r
                  JOIN alunos a ON r.numero_chamada = a.numero_chamada AND r.id_turma = a.id_turma
                  WHERE DATE(r.hora_saida) = CURDATE()";

        if ($id_turma) {
            $query .= " AND r.id_turma = :id_turma";
        }
        $query .= " ORDER BY r.hora_saida DESC";

        $stmt = $this->conn->prepare($query);
        if ($id_turma) {
            $stmt->bindParam(":id_turma", $id_turma, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Dashboard: Estatísticas de hoje
    public function getEstatisticasHoje($id_turma = null)
    {
        $query = "SELECT 
                    COUNT(*) as total_saidas,
                    COUNT(DISTINCT r.numero_chamada) as total_alunos_distintos,
                    COALESCE(SUM(r.duracao_minutos), 0) as tempo_total_gasto,
                    COALESCE(AVG(r.duracao_minutos), 0) as tempo_medio
                  FROM registros_saida r
                  JOIN alunos a ON r.numero_chamada = a.numero_chamada AND r.id_turma = a.id_turma
                  WHERE DATE(r.hora_saida) = CURDATE() AND r.status_alunos = 'CONCLUIDO'";

        if ($id_turma) {
            $query .= " AND r.id_turma = :id_turma";
        }

        $stmt = $this->conn->prepare($query);
        if ($id_turma) {
            $stmt->bindParam(":id_turma", $id_turma, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Dashboard: Ranking
    public function getRankingHoje($id_turma = null)
    {
        $query = "SELECT r.numero_chamada, r.id_turma, a.nome, 
                         COUNT(*) as frequencia, 
                         COALESCE(SUM(r.duracao_minutos), 0) as tempo_acumulado
                  FROM registros_saida r
                  JOIN alunos a ON r.numero_chamada = a.numero_chamada AND r.id_turma = a.id_turma
                  WHERE DATE(r.hora_saida) = CURDATE() AND r.status_alunos = 'CONCLUIDO'";

        if ($id_turma) {
            $query .= " AND r.id_turma = :id_turma";
        }

        $query .= " GROUP BY r.numero_chamada, r.id_turma, a.nome
                  ORDER BY tempo_acumulado DESC, frequencia DESC";
        $stmt = $this->conn->prepare($query);
        if ($id_turma) {
            $stmt->bindParam(":id_turma", $id_turma, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Calendário: Filtra por data e lista todos
    public function getRegistrosPorData($data, $id_turma = null)
    {
        $query = "SELECT r.id, r.numero_chamada, r.id_turma, a.nome, r.hora_saida, r.hora_retorno, r.duracao_minutos as tempo_gasto 
                  FROM registros_saida r
                  JOIN alunos a ON r.numero_chamada = a.numero_chamada AND r.id_turma = a.id_turma
                  WHERE DATE(r.hora_saida) = :data";

        if ($id_turma) {
            $query .= " AND r.id_turma = :id_turma";
        }
        $query .= " ORDER BY r.hora_saida ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":data", $data);
        if ($id_turma) {
            $stmt->bindParam(":id_turma", $id_turma, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
