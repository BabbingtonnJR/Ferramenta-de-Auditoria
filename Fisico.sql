CREATE DATABASE ChecklistAuditoria;
USE ChecklistAuditoria;

CREATE TABLE Checklist (
	id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(50) NOT NULL,
    descricao VARCHAR(100) NOT NULL,
    data_criacao DATETIME
);

CREATE TABLE Item (
	id INT AUTO_INCREMENT PRIMARY KEY,
    id_checklist INT,
    descricao VARCHAR(100) NOT NULL,
    conformidade VARCHAR(30) NOT NULL,
    FOREIGN KEY (id_checklist) REFERENCES Checklist(id)
);


CREATE TABLE NC (
	id INT AUTO_INCREMENT PRIMARY KEY,
    id_item INT,
	data_criacao DATETIME,
    descricao VARCHAR(100) NOT NULL,
    estado VARCHAR(30) NOT NULL,
    prioridade VARCHAR(30) NOT NULL,
    FOREIGN KEY (id_item) REFERENCES Item(id)
);

CREATE TABLE Escalonamento (
	id INT AUTO_INCREMENT PRIMARY KEY,
    id_nc INT,
    prazo DATETIME,
    estado VARCHAR(30) NOT NULL,
    data_criacao DATETIME,
    data_conclusao DATETIME,
    responsavel VARCHAR(100) NOT NULL,
    FOREIGN KEY (id_nc) REFERENCES NC(id)
);

CREATE TABLE Email (
	id INT AUTO_INCREMENT PRIMARY KEY,
    id_nc INT,
    data_envio DATETIME,
    email_destinatario VARCHAR(100) NOT NULL,
    email_remetente VARCHAR(100) NOT NULL,
    FOREIGN KEY (id_nc) REFERENCES NC(id)
);
