-- Criação do Banco de Dados
CREATE DATABASE IF NOT EXISTS sistema_banheiro;
USE sistema_banheiro;

-- Tabela de Alunos
CREATE TABLE IF NOT EXISTS alunos (
    id_alunos INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL
);

-- Tabela de Registros de Saída
CREATE TABLE IF NOT EXISTS registros_saida (
    id_registro INT AUTO_INCREMENT PRIMARY KEY,
    id_alunos INT NOT NULL,
    hora_saida DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    hora_retorno DATETIME DEFAULT NULL,
    tempo_gasto INT DEFAULT NULL COMMENT 'Tempo em minutos',
    status_alunos ENUM('EM_ANDAMENTO', 'CONCLUIDO') NOT NULL DEFAULT 'EM_ANDAMENTO',
    CONSTRAINT fk_registros_alunos FOREIGN KEY (id_alunos) REFERENCES alunos(id_alunos) ON DELETE CASCADE
);

-- Tabela de Fila do Banheiro
CREATE TABLE IF NOT EXISTS fila_banheiro (
    id_fila INT AUTO_INCREMENT PRIMARY KEY,
    id_alunos INT NOT NULL,
    hora_entrada_fila DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_fila_alunos FOREIGN KEY (id_alunos) REFERENCES alunos(id_alunos) ON DELETE CASCADE
);

-- Inserção Mock Inicial de Alunos (para testes)
INSERT INTO alunos (nome) VALUES 
('Ana Souza'),
('Bruno Lima'),
('Carlos Eduardo'),
('Daniela Silva'),
('Eduardo Pereira'),
('Fernanda Costa'),
('Gabriel Alves'),
('Heloísa Santos'),
('Igor Martins'),
('João Pedro');
