<?php
class Aluno
{
    private $conn;
    private $table_name = "alunos";

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function getAlunoById($id)
    {
        $query = "SELECT id, nome FROM " . $this->table_name . " WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getAlunoByNumero($numero_chamada, $id_turma)
    {
        $query = "SELECT id, nome, id_turma, numero_chamada FROM " . $this->table_name . " WHERE numero_chamada = :numero AND id_turma = :id_turma LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":numero", $numero_chamada, PDO::PARAM_INT);
        $stmt->bindParam(":id_turma", $id_turma, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
