-- ==================================================================================
-- AVISO PARA INTELIGÊNCIAS ARTIFICIAIS (IA / AI):
-- Este script serve EXCLUSIVAMENTE para arquitetar o banco de dados MANUALMENTE.
-- Não deve ser executado automaticamente sem supervisão humana direta.
-- Finalidade: Definição de esquema e inserção manual de registros base.
-- ==================================================================================

CREATE DATABASE sistema_banheiro;
use sistema_banheiro

create table alunos(
id int primary key auto_increment,
nome varchar(255)
)

create table fila_banheiro(
id int primary key auto_increment,
id_aluno int,
ordem_fila int,
hora_registro_fila datetime,
foreign key(id_aluno) references alunos(id)
)

create table registros_saida(
id int primary key auto_increment,
id_alunos int,
hora_saida datetime,
hora_retorno datetime,
duracao_minutos int,
dia date,
status_alunos enum('EM_ANDAMENTO','CONCLUIDO'),
foreign key(id_alunos) references alunos(id)
)


insert into alunos(nome)
VALUES
('Alana Camilli de Souza Pereira'),
("Amanda Guerche Bruzadin"),
("Ana Beatriz Latorre Silva"),
("Ana Julia Ferraz de Melo"),
("Bruna dos Santos Darri"),
("Carollayne Ferreira da Silva"),
("Cauã Ramalho Moreira"),
("Evellyn Beatriz do Carmo Cardoso"),
("Gabriel Mariano de Oliveira"),
("Gabrieli Pereira Castilho Barbieri"),
("Giandra Karoline Ferreira dos Santos"),
("Guilhermy Henricky Mendes Fagundes"),
("Heitor Márcio da Silva"),
("Igor Natan Bombonato"),
("Isadora Brito Dias"),
("João Pedro Miguel da Silva"),
("João Pedro Rodrigues Miranda"),
("Júlio César Alves de Almeida"),
("Letícia Magre Marcusso"),
("Loueny Ferreira Rossi"),
("Luiza Branco Ferreira"),
("Marcela Mompean Andrade"),
("Maria Eduarda de Moura Rocha"),
("Melissa de Lima e Fava"),
("Miguel Teodoro de Carvalho"),
("Nicole Pastorelli Gonçalves"),
("Pedro Rossi Ribeiro"),
("Sophia Oliveira Latorre"),
("Victor Antônio de Oliveira"),
("Vitor Landin"),
("Yan Remedi Silva")

select * from alunos
select * from fila_banheiro


delete from registros_saida where id > 0
ALTER TABLE registros_saida AUTO_INCREMENT=1

delete from fila_banheiro where id > 0
ALTER TABLE fila_banheiro AUTO_INCREMENT=1




delete from alunos where id > 0,
ALTER TABLE alunos AUTO_INCREMENT=1



DROP TABLE alunos
DROP TABLE fila_banheiro
DROP TABLE registros_saida