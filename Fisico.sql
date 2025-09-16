DROP DATABASE IF EXISTS ChecklistAuditoria;
CREATE DATABASE ChecklistAuditoria;
USE ChecklistAuditoria;

CREATE TABLE Checklist (
	id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(50) NOT NULL,
    descricao VARCHAR(100) NOT NULL,
    data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE Item (
	id INT AUTO_INCREMENT PRIMARY KEY,
    descricao VARCHAR(100) NOT NULL,
    conformidade VARCHAR(30),
    numero_item INT NOT NULL
);

CREATE TABLE Prazo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(50) NOT NULL,
    dias INT NOT NULL,
    id_checklist INT NOT NULL,
    FOREIGN KEY (id_checklist) REFERENCES Checklist(id)
);

CREATE TABLE naoConformidade (
	id INT AUTO_INCREMENT PRIMARY KEY,
    id_item INT,
    id_prazo INT,
	data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
    descricao VARCHAR(100) NOT NULL,
    estado VARCHAR(30) NOT NULL,
    prioridade VARCHAR(30) NOT NULL,
    FOREIGN KEY (id_item) REFERENCES Item(id),
    FOREIGN KEY (id_prazo) REFERENCES Prazo(id)
);

CREATE TABLE Escalonamento (
	id INT AUTO_INCREMENT PRIMARY KEY,
    id_nc INT,
    id_prazo INT,
    prazo DATETIME,
    superior_responsavel VARCHAR(100),
    estado VARCHAR(30) NOT NULL,
    data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
    data_conclusao DATETIME,
    responsavel VARCHAR(100) NOT NULL,
    FOREIGN KEY (id_nc) REFERENCES naoConformidade(id),
	FOREIGN KEY (id_prazo) REFERENCES Prazo(id)
);

CREATE TABLE Email (
	id INT AUTO_INCREMENT PRIMARY KEY,
    id_nc INT,
    data_envio DATETIME DEFAULT CURRENT_TIMESTAMP,
    email_destinatario VARCHAR(100) NOT NULL,
    email_remetente VARCHAR(100) NOT NULL,
    FOREIGN KEY (id_nc) REFERENCES naoConformidade(id)
);

CREATE TABLE Item_checklist (
    id_checklist INT NOT NULL,
    FOREIGN KEY (id_checklist) REFERENCES Checklist(id),
    id_item INT NOT NULL,
    FOREIGN KEY (id_item) REFERENCES Item(id)
);

