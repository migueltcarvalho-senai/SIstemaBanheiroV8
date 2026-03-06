<?php
class Registro {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Busca aluno que está no banheiro (ativo)
    public function getAtivo() {
        $query = "SELECT r.id, r.id_alunos, a.nome, r.hora_saida 
                  FROM registros_saida r
                  JOIN alunos a ON r.id_alunos = a.id
                  WHERE r.status_alunos = 'EM_ANDAMENTO' 
                  LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Lista fila de espera
    public function getFila() {
        $query = "SELECT f.id, f.id_aluno as id_alunos, a.nome, f.hora_registro_fila as hora_entrada_fila
                  FROM fila_banheiro f
                  JOIN alunos a ON f.id_aluno = a.id
                  ORDER BY f.hora_registro_fila ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Adiciona aluno à fila
    public function entrarFila($id_alunos) {
        // Verifica se já está na fila
        $query_check = "SELECT id FROM fila_banheiro WHERE id_aluno = :id_alunos";
        $stmt_check = $this->conn->prepare($query_check);
        $stmt_check->bindParam(":id_alunos", $id_alunos);
        $stmt_check->execute();
        if ($stmt_check->rowCount() > 0) return false;

        $query = "INSERT INTO fila_banheiro (id_aluno, hora_registro_fila) VALUES (:id_alunos, NOW())";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id_alunos", $id_alunos);
        return $stmt->execute();
    }

    // Registra a saída (entra no banheiro)
    public function registrarSaida($id_alunos) {
        // Verifica se já existe alguém (sistema trava para 1 por vez de fora)
        if ($this->getAtivo()) return false;

        $query = "INSERT INTO registros_saida (id_alunos, hora_saida, status_alunos) 
                  VALUES (:id_alunos, NOW(), 'EM_ANDAMENTO')";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id_alunos", $id_alunos);
        return $stmt->execute();
    }

    // Registra o retorno (sai do banheiro) e opcionalmente puxa próximo
    public function registrarRetorno($id_alunos) {
        try {
            $this->conn->beginTransaction();

            $query = "UPDATE registros_saida 
                      SET hora_retorno = NOW(), 
                          duracao_minutos = TIMESTAMPDIFF(MINUTE, hora_saida, NOW()),
                          status_alunos = 'CONCLUIDO' 
                      WHERE id_alunos = :id_alunos AND status_alunos = 'EM_ANDAMENTO'";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id_alunos", $id_alunos);
            $stmt->execute();

            // Puxar o próximo da fila
            $this->proximoDaFila();

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    // Move primeiro da fila para o banheiro
    private function proximoDaFila() {
        $fila = $this->getFila(); // Já traz mapeado
        if (count($fila) > 0) {
            $proximo = $fila[0];
            $id_proximo = $proximo['id_alunos'];
            $id_fila = $proximo['id'];

            // Remove da fila
            $q_del = "DELETE FROM fila_banheiro WHERE id = :id_fila";
            $s_del = $this->conn->prepare($q_del);
            $s_del->bindParam(":id_fila", $id_fila);
            $s_del->execute();

            // Registra saída do próximo
            $q_insert = "INSERT INTO registros_saida (id_alunos, hora_saida, status_alunos) 
                         VALUES (:id_alunos, NOW(), 'EM_ANDAMENTO')";
            $s_insert = $this->conn->prepare($q_insert);
            $s_insert->bindParam(":id_alunos", $id_proximo);
            $s_insert->execute();
        }
    }

    // Retorna todos os registros de hoje
    public function getRegistrosHoje() {
        $query = "SELECT r.id, r.id_alunos, a.nome, r.hora_saida, r.hora_retorno, r.duracao_minutos as tempo_gasto, r.status_alunos 
                  FROM registros_saida r
                  JOIN alunos a ON r.id_alunos = a.id
                  WHERE DATE(r.hora_saida) = CURDATE()
                  ORDER BY r.hora_saida DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Dashboard: Estatísticas de hoje
    public function getEstatisticasHoje() {
        $query = "SELECT 
                    COUNT(*) as total_saidas,
                    COUNT(DISTINCT id_alunos) as total_alunos_distintos,
                    COALESCE(SUM(duracao_minutos), 0) as tempo_total_gasto,
                    COALESCE(AVG(duracao_minutos), 0) as tempo_medio
                  FROM registros_saida 
                  WHERE DATE(hora_saida) = CURDATE() AND status_alunos = 'CONCLUIDO'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Dashboard: Ranking
    public function getRankingHoje() {
        $query = "SELECT r.id_alunos, a.nome, 
                         COUNT(*) as frequencia, 
                         COALESCE(SUM(duracao_minutos), 0) as tempo_acumulado
                  FROM registros_saida r
                  JOIN alunos a ON r.id_alunos = a.id
                  WHERE DATE(r.hora_saida) = CURDATE() AND r.status_alunos = 'CONCLUIDO'
                  GROUP BY r.id_alunos, a.nome
                  ORDER BY tempo_acumulado DESC, frequencia DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Calendário: Filtra por data e lista todos
    public function getRegistrosPorData($data) {
        $query = "SELECT r.id, r.id_alunos, a.nome, r.hora_saida, r.hora_retorno, r.duracao_minutos as tempo_gasto 
                  FROM registros_saida r
                  JOIN alunos a ON r.id_alunos = a.id
                  WHERE DATE(r.hora_saida) = :data
                  ORDER BY r.hora_saida ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":data", $data);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
