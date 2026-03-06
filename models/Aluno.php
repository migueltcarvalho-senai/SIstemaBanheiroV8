<?php
class Aluno {
    private $conn;
    private $table_name = "alunos";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAlunoById($id) {
        $query = "SELECT id, nome FROM " . $this->table_name . " WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
