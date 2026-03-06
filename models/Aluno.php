<?php
class Aluno {
    private $conn;
    private $table_name = "alunos";

    public $id;
    public $nome;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Busca aluno por ID
    public function getAlunoById($id) {
        $query = "SELECT id, nome FROM " . $this->table_name . " WHERE id = :id LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if($row) {
            $this->id = $row['id'];
            $this->nome = $row['nome'];
            return true;
        }
        return false;
    }
}
?>
