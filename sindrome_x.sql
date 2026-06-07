CREATE DATABASE IF NOT EXISTS sindrome_x;
USE sindrome_x;

-- =====================================================
-- TABELAS DE USUÁRIOS E PACIENTES
-- =====================================================

CREATE TABLE profissional_saude (
    id_profissional INT AUTO_INCREMENT PRIMARY KEY,
    nome_completo VARCHAR(150) NOT NULL,
    registro_profissional VARCHAR(255) UNIQUE NOT NULL,
    especialidade VARCHAR(100) NOT NULL,
    email VARCHAR(255) UNIQUE,
    telefone VARCHAR(255),
    instituicao VARCHAR(200),
    senha_profissional VARCHAR(255) NOT NULL,
    token VARCHAR(255) UNIQUE,
    data_expiracao DATETIME,
    nivel TINYINT NOT NULL DEFAULT 0
);

-- Trigger: Primeiro profissional cadastrado vira ADMIN (Nível 2)
DELIMITER //
CREATE TRIGGER tg_primeiro_id_nivel_2
BEFORE INSERT ON profissional_saude
FOR EACH ROW
BEGIN
    DECLARE total_registros INT;
    SELECT COUNT(*) INTO total_registros FROM profissional_saude;
    IF total_registros = 0 THEN
        SET NEW.nivel = 2;
    END IF;
END //
DELIMITER ;

CREATE TABLE paciente_titular (
    id_paciente INT AUTO_INCREMENT PRIMARY KEY,
    numero_inscricao VARCHAR(30) UNIQUE NOT NULL,
    nome_completo VARCHAR(150) NOT NULL,
    cpf VARCHAR(255) UNIQUE NOT NULL,
    data_nascimento DATE NOT NULL,
    sexo ENUM('Masculino','Feminino') NOT NULL,
    email VARCHAR(255),
    telefone VARCHAR(255),
    endereco VARCHAR(255),
    id_profissional INT NOT NULL, 
    id_profissional_atual INT NOT NULL,
    FOREIGN KEY (id_profissional) REFERENCES profissional_saude(id_profissional),
    FOREIGN KEY (id_profissional_atual) REFERENCES profissional_saude(id_profissional)
);

CREATE TABLE responsavel_legal (
    id_responsavel INT AUTO_INCREMENT PRIMARY KEY,
    nome_completo VARCHAR(150) NOT NULL,
    email VARCHAR(255),
    id_paciente INT NOT NULL,
    telefone VARCHAR(255),
    parentesco VARCHAR(50),
    FOREIGN KEY (id_paciente) REFERENCES paciente_titular(id_paciente)
);

-- Trigger: Limite de 4 responsáveis por paciente
DELIMITER //
CREATE TRIGGER limite_responsaveis
BEFORE INSERT ON responsavel_legal
FOR EACH ROW
BEGIN
    DECLARE total INT;
    SELECT COUNT(*) INTO total FROM responsavel_legal WHERE id_paciente = NEW.id_paciente;
    IF total >= 4 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Este paciente já possui 4 responsáveis';
    END IF;
END //
DELIMITER ;

-- =====================================================
-- TABELAS DE AVALIAÇÃO E SINTOMAS
-- =====================================================

CREATE TABLE avaliacao_clinica (
    id_avaliacao INT AUTO_INCREMENT PRIMARY KEY,
    id_profissional INT NOT NULL,
    id_paciente INT NOT NULL,
    data_avaliacao DATETIME NOT NULL,
    score DECIMAL(5,2),
    classificacao_risco ENUM('Baixo Risco','Suspeito'),
    historico_familiar TEXT,
    observacoes TEXT,
    FOREIGN KEY (id_profissional) REFERENCES profissional_saude(id_profissional),
    FOREIGN KEY (id_paciente) REFERENCES paciente_titular(id_paciente)
);

CREATE TABLE sintoma (
    id_sintoma INT AUTO_INCREMENT PRIMARY KEY,
    nome_sintoma VARCHAR(100) NOT NULL
);

CREATE TABLE peso_sintoma (
    id_peso INT AUTO_INCREMENT PRIMARY KEY,
    id_sintoma INT NOT NULL,
    sexo ENUM('Masculino','Feminino') NOT NULL,
    peso DECIMAL(5,2) NOT NULL,
    FOREIGN KEY (id_sintoma) REFERENCES sintoma(id_sintoma)
);

CREATE TABLE avaliacao_sintoma (
    id_avaliacao INT,
    id_sintoma INT,
    presente BOOLEAN NOT NULL,
    peso_aplicado DECIMAL(5,2),
    PRIMARY KEY (id_avaliacao, id_sintoma),
    FOREIGN KEY (id_avaliacao) REFERENCES avaliacao_clinica(id_avaliacao),
    FOREIGN KEY (id_sintoma) REFERENCES sintoma(id_sintoma)
);

CREATE TABLE teste_genetico (
    id_teste INT AUTO_INCREMENT PRIMARY KEY,
    id_avaliacao INT NOT NULL,
    tipo_teste ENUM('PCR','Southern Blotting') NOT NULL,
    resultado ENUM('Normal','Zona Cinzenta','Pre-mutacao','Mutacao Completa'),
    data_encaminhamento DATE NOT NULL,
    FOREIGN KEY (id_avaliacao) REFERENCES avaliacao_clinica(id_avaliacao)
);

-- =====================================================
-- TRIGGERS CORRIGIDOS
-- =====================================================

DELIMITER //

-- 1. Aplicar peso do sintoma (CORRIGIDO)
CREATE TRIGGER aplicar_peso_sintoma
BEFORE INSERT ON avaliacao_sintoma
FOR EACH ROW
BEGIN
    DECLARE sexo_paciente ENUM('Masculino','Feminino');
    DECLARE v_peso DECIMAL(5,2) DEFAULT 0;

    -- Busca o sexo do paciente
    SELECT p.sexo INTO sexo_paciente
    FROM avaliacao_clinica a
    JOIN paciente_titular p ON a.id_paciente = p.id_paciente
    WHERE a.id_avaliacao = NEW.id_avaliacao;

    -- Apenas busca peso se o sexo foi encontrado e o sintoma está presente
    IF sexo_paciente IS NOT NULL AND NEW.presente = TRUE THEN
        SELECT peso INTO v_peso 
        FROM peso_sintoma 
        WHERE id_sintoma = NEW.id_sintoma AND sexo = sexo_paciente;
    END IF;

    SET NEW.peso_aplicado = IFNULL(v_peso, 0);
END //

-- 2. Classificar risco automaticamente
CREATE TRIGGER classificar_risco
BEFORE UPDATE ON avaliacao_clinica
FOR EACH ROW
BEGIN
    DECLARE sexo_paciente ENUM('Masculino','Feminino');
    SELECT sexo INTO sexo_paciente FROM paciente_titular WHERE id_paciente = NEW.id_paciente;

    IF ((sexo_paciente = 'Masculino' AND NEW.score >= 0.56) OR 
        (sexo_paciente = 'Feminino' AND NEW.score >= 0.55)) THEN
        SET NEW.classificacao_risco = 'Suspeito';
    ELSE
        SET NEW.classificacao_risco = 'Baixo Risco';
    END IF;
END //

-- 3. Atualizar score da avaliação
CREATE TRIGGER atualizar_score
AFTER INSERT ON avaliacao_sintoma
FOR EACH ROW
BEGIN
    DECLARE total_score DECIMAL(5,2);
    SELECT SUM(peso_aplicado) INTO total_score FROM avaliacao_sintoma WHERE id_avaliacao = NEW.id_avaliacao;
    UPDATE avaliacao_clinica SET score = total_score WHERE id_avaliacao = NEW.id_avaliacao;
END //

DELIMITER ;

-- =====================================================
-- INSERÇÃO DE DADOS INICIAIS
-- =====================================================

INSERT INTO sintoma (nome_sintoma) VALUES 
('Atraso na fala'), ('Dificuldades de aprendizagem'), ('Déficit de atenção'), 
('Deficiência intelectual'), ('Hiperatividade'), ('Comportamento agressivo'), 
('Evita contato visual'), ('Evita contato físico'), ('Movimentos repetitivos e rítmicos'), 
('Hipermobilidade articular'), ('Macroorquidia'), ('Face alongada / orelhas salientes');

INSERT INTO peso_sintoma (id_sintoma, sexo, peso) VALUES
(1, 'Masculino', 0.14), (2, 'Masculino', 0.18), (3, 'Masculino', 0.17), (4, 'Masculino', 0.32),
(5, 'Masculino', 0.12), (6, 'Masculino', 0.01), (7, 'Masculino', 0.06), (8, 'Masculino', 0.04),
(9, 'Masculino', 0.17), (10, 'Masculino', 0.19), (11, 'Masculino', 0.26), (12, 'Masculino', 0.29),
(1, 'Feminino', 0.01), (2, 'Feminino', 0.28), (3, 'Feminino', 0.12), (4, 'Feminino', 0.20),
(5, 'Feminino', 0.04), (6, 'Feminino', 0.02), (7, 'Feminino', 0.08), (8, 'Feminino', 0.07),
(9, 'Feminino', 0.05), (10, 'Feminino', 0.04), (11, 'Feminino', 0.00), (12, 'Feminino', 0.09);