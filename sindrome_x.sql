CREATE DATABASE sindrome_x;
USE sindrome_x;

-- CRIAÇÃO DAS TABELAS

CREATE TABLE profissional_saude (
    id_profissional INT AUTO_INCREMENT PRIMARY KEY,
    nome_completo VARCHAR(150) NOT NULL,
    registro_profissional VARCHAR(30) UNIQUE NOT NULL,
    especialidade VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE,
    telefone VARCHAR(20),
    instituicao VARCHAR(200),
    senha_profissional VARCHAR(255) NOT NULL,
    
    token VARCHAR(255) UNIQUE,
    data_expiracao DATETIME
);

-- =====================================================

CREATE TABLE paciente_titular (
    id_paciente INT AUTO_INCREMENT PRIMARY KEY,

    numero_inscricao VARCHAR(30)
    UNIQUE NOT NULL,

    nome_completo VARCHAR(150) NOT NULL,

    cpf VARCHAR(14)
    UNIQUE NOT NULL,

    data_nascimento DATE NOT NULL,

    sexo ENUM('Masculino','Feminino')
    NOT NULL,

    email VARCHAR(100),
    telefone VARCHAR(20),

    endereco TEXT,

    -- médico que cadastrou
    id_profissional INT NOT NULL,

    -- médico responsável atualmente
    id_profissional_atual INT NOT NULL,

    FOREIGN KEY (id_profissional)
    REFERENCES profissional_saude(id_profissional),

    FOREIGN KEY (id_profissional_atual)
    REFERENCES profissional_saude(id_profissional)
);
-- =====================================================

CREATE TABLE dependente (
    id_dependente INT AUTO_INCREMENT PRIMARY KEY,

    numero_inscricao VARCHAR(30)
    UNIQUE NOT NULL,

    nome_completo VARCHAR(150)
    NOT NULL,

    data_nascimento DATE NOT NULL,

    sexo ENUM('Masculino','Feminino')
    NOT NULL,

    email VARCHAR(100),

    id_titular INT NOT NULL,

    FOREIGN KEY (id_titular)
    REFERENCES paciente_titular(id_paciente)
);

-- =====================================================

CREATE TABLE avaliacao_clinica (
    id_avaliacao INT AUTO_INCREMENT PRIMARY KEY,

    id_profissional INT NOT NULL,

    -- titular OU dependente
    id_paciente INT NULL,
    id_dependente INT NULL,

    -- data e horário da avaliação
    data_avaliacao DATETIME NOT NULL,

    score DECIMAL(5,2),

    classificacao_risco
    ENUM('Baixo Risco','Suspeito'),

    historico_familiar TEXT,
    observacoes TEXT,

    FOREIGN KEY (id_profissional)
    REFERENCES profissional_saude(id_profissional),

    FOREIGN KEY (id_paciente)
    REFERENCES paciente_titular(id_paciente),

    FOREIGN KEY (id_dependente)
    REFERENCES dependente(id_dependente),

    CONSTRAINT chk_paciente
    CHECK (
        id_paciente IS NOT NULL
        OR
        id_dependente IS NOT NULL
    )
);

-- =====================================================

CREATE TABLE sintoma (
    id_sintoma INT AUTO_INCREMENT PRIMARY KEY,

    nome_sintoma VARCHAR(100)
    NOT NULL
);

-- =====================================================

CREATE TABLE peso_sintoma (
    id_peso INT AUTO_INCREMENT PRIMARY KEY,

    id_sintoma INT NOT NULL,

    sexo ENUM('Masculino','Feminino')
    NOT NULL,

    peso DECIMAL(5,2)
    NOT NULL,

    FOREIGN KEY (id_sintoma)
    REFERENCES sintoma(id_sintoma)
);

-- =====================================================

CREATE TABLE avaliacao_sintoma (
    id_avaliacao INT,

    id_sintoma INT,

    -- TRUE = presente
    -- FALSE = ausente
    presente BOOLEAN NOT NULL,

    peso_aplicado DECIMAL(5,2),

    PRIMARY KEY (id_avaliacao, id_sintoma),

    FOREIGN KEY (id_avaliacao)
    REFERENCES avaliacao_clinica(id_avaliacao),

    FOREIGN KEY (id_sintoma)
    REFERENCES sintoma(id_sintoma)
);

-- =====================================================

CREATE TABLE teste_genetico (
    id_teste INT AUTO_INCREMENT PRIMARY KEY,

    id_avaliacao INT NOT NULL,

    tipo_teste ENUM(
        'PCR',
        'Southern Blotting'
    ) NOT NULL,

    resultado ENUM(
        'Normal',
        'Zona Cinzenta',
        'Pre-mutacao',
        'Mutacao Completa'
    ),

    data_encaminhamento DATE NOT NULL,

    FOREIGN KEY (id_avaliacao)
    REFERENCES avaliacao_clinica(id_avaliacao)
);



DELIMITER ;


-- Máximo de 4 dependentes


DELIMITER //

CREATE TRIGGER limite_dependentes
BEFORE INSERT ON dependente
FOR EACH ROW
BEGIN

    DECLARE total INT;

    SELECT COUNT(*) INTO total
    FROM dependente
    WHERE id_titular = NEW.id_titular;

    IF total >= 4 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Titular já possui 4 dependentes';
    END IF;

END //

DELIMITER ;

-- aplicação de peso

DELIMITER //

CREATE TRIGGER aplicar_peso_sintoma
BEFORE INSERT ON avaliacao_sintoma
FOR EACH ROW
BEGIN

    DECLARE sexo_paciente ENUM('Masculino','Feminino');
    DECLARE v_peso DECIMAL(5,2);

    -- Busca sexo do titular
    SELECT p.sexo
    INTO sexo_paciente
    FROM avaliacao_clinica a
    JOIN paciente_titular p
        ON a.id_paciente = p.id_paciente
    WHERE a.id_avaliacao = NEW.id_avaliacao;

    -- Se não encontrou, busca dependente
    IF sexo_paciente IS NULL THEN

        SELECT d.sexo
        INTO sexo_paciente
        FROM avaliacao_clinica a
        JOIN dependente d
            ON a.id_dependente = d.id_dependente
        WHERE a.id_avaliacao = NEW.id_avaliacao;

    END IF;

    -- Aplica peso se sintoma estiver presente
    IF NEW.presente = TRUE THEN

        SELECT peso
        INTO v_peso
        FROM peso_sintoma
        WHERE id_sintoma = NEW.id_sintoma
        AND sexo = sexo_paciente;

        SET NEW.peso_aplicado = v_peso;

    ELSE

        SET NEW.peso_aplicado = 0;

    END IF;

END //

DELIMITER ;

-- classificacao risco

DELIMITER //

CREATE TRIGGER classificar_risco
BEFORE UPDATE ON avaliacao_clinica
FOR EACH ROW
BEGIN

    DECLARE sexo_paciente ENUM('Masculino','Feminino');

    -- Busca sexo do paciente titular
    IF NEW.id_paciente IS NOT NULL THEN

        SELECT sexo
        INTO sexo_paciente
        FROM paciente_titular
        WHERE id_paciente = NEW.id_paciente;

    -- Caso seja dependente
    ELSE

        SELECT sexo
        INTO sexo_paciente
        FROM dependente
        WHERE id_dependente = NEW.id_dependente;

    END IF;

    -- Classificação conforme sexo
    IF (
        (sexo_paciente = 'Masculino' AND NEW.score >= 0.56)
        OR
        (sexo_paciente = 'Feminino' AND NEW.score >= 0.55)
    ) THEN

        SET NEW.classificacao_risco = 'Suspeito';

    ELSE

        SET NEW.classificacao_risco = 'Baixo Risco';

    END IF;

END //

DELIMITER ;


-- atualizar score

DELIMITER //


CREATE TRIGGER atualizar_score
AFTER INSERT ON avaliacao_sintoma
FOR EACH ROW
BEGIN

    DECLARE total_score DECIMAL(5,2);

    
    SELECT SUM(peso_aplicado)
    INTO total_score
    FROM avaliacao_sintoma
    WHERE id_avaliacao = NEW.id_avaliacao;

    -- Atualiza score
    UPDATE avaliacao_clinica
    SET score = total_score
    WHERE id_avaliacao = NEW.id_avaliacao;

END //

DELIMITER ;

-- Remove a trava de validação dupla
ALTER TABLE avaliacao_clinica DROP CHECK chk_paciente;

-- Torna o id_paciente obrigatório (não aceita mais nulo)
ALTER TABLE avaliacao_clinica MODIFY id_paciente INT NOT NULL;

-- Inserindo os sintomas na tabela sintoma
INSERT INTO sintoma (nome_sintoma) VALUES 
('Atraso na fala'),
('Dificuldades de aprendizagem'),
('Déficit de atenção'),
('Deficiência intelectual'),
('Hiperatividade'),
('Comportamento agressivo'),
('Evita contato visual'),
('Evita contato físico'),
('Movimentos repetitivos e rítmicos'),
('Hipermobilidade articular'),
('Macroorquidia'),
('Face alongada / orelhas salientes');


-- Masculino
INSERT INTO peso_sintoma (id_sintoma, sexo, peso) VALUES
(1, 'Masculino', 0.14), -- Atraso na fala
(2, 'Masculino', 0.18), -- Dificuldades de aprendizagem
(3, 'Masculino', 0.17), -- Déficit de atenção
(4, 'Masculino', 0.32), -- Deficiência intelectual
(5, 'Masculino', 0.12), -- Hiperatividade
(6, 'Masculino', 0.01), -- Comportamento agressivo
(7, 'Masculino', 0.06), -- Evita contato visual
(8, 'Masculino', 0.04), -- Evita contato físico
(9, 'Masculino', 0.17), -- Movimentos repetitivos
(10, 'Masculino', 0.19), -- Hipermobilidade articular
(11, 'Masculino', 0.26), -- Macroorquidia
(12, 'Masculino', 0.29); -- Face alongada / orelhas salientes

-- Feminino
INSERT INTO peso_sintoma (id_sintoma, sexo, peso) VALUES
(1, 'Feminino', 0.01), -- Atraso na fala
(2, 'Feminino', 0.28), -- Dificuldades de aprendizagem
(3, 'Feminino', 0.12), -- Déficit de atenção
(4, 'Feminino', 0.20), -- Deficiência intelectual
(5, 'Feminino', 0.04), -- Hiperatividade
(6, 'Feminino', 0.02), -- Comportamento agressivo
(7, 'Feminino', 0.08), -- Evita contato visual
(8, 'Feminino', 0.07), -- Evita contato físico
(9, 'Feminino', 0.05), -- Movimentos repetitivos
(10, 'Feminino', 0.04), -- Hipermobilidade articular
(11, 'Feminino', 0.00), -- Macroorquidia
(12, 'Feminino', 0.09); -- Face alongada / orelhas salientes


-- Sistema de privileges, nivel 1, 2 e 3
-- NOVO PROFISSIONAL NIVEL 0
ALTER TABLE profissional_saude ADD COLUMN nivel TINYINT NOT NULL DEFAULT 0;

-- Muda o nível do primeiro profissional para adm
UPDATE profissional_saude SET nivel = 2 WHERE id_profissional = 1;

-- ALTER TABLE PARA MUDAR DEPENDENTE PARA
-- RESPONSÁVEL LEGAL
ALTER TABLE dependente RENAME TO responsavel_legal;
ALTER TABLE responsavel_legal RENAME COLUMN id_dependente TO id_responsavel;
ALTER TABLE responsavel_legal DROP COLUMN numero_inscricao;
ALTER TABLE responsavel_legal DROP COLUMN data_nascimento;
ALTER TABLE responsavel_legal DROP COLUMN sexo;
ALTER TABLE responsavel_legal ADD COLUMN telefone VARCHAR(20);
ALTER TABLE responsavel_legal ADD COLUMN parentesco VARCHAR(50);
ALTER TABLE responsavel_legal RENAME COLUMN id_titular TO id_paciente;
ALTER TABLE responsavel_legal DROP FOREIGN KEY responsavel_legal_ibfk_1;
ALTER TABLE responsavel_legal ADD FOREIGN KEY (id_paciente) REFERENCES paciente_titular(id_paciente);

-- MUDANDO TRIGGERS DEPENDENTE
DROP TRIGGER limite_dependentes;
DROP TRIGGER aplicar_peso_sintoma;
DROP TRIGGER classificar_risco;

DELIMITER //

CREATE TRIGGER limite_dependentes
BEFORE INSERT ON responsavel_legal
FOR EACH ROW
BEGIN
    DECLARE total INT;
    SELECT COUNT(*) INTO total
    FROM responsavel_legal
    WHERE id_paciente = NEW.id_paciente;
    IF total >= 4 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Este paciente já possui 4 responsáveis';
    END IF;
END //

CREATE TRIGGER aplicar_peso_sintoma
BEFORE INSERT ON avaliacao_sintoma
FOR EACH ROW
BEGIN
    DECLARE sexo_paciente ENUM('Masculino','Feminino');
    DECLARE v_peso DECIMAL(5,2);

    SELECT p.sexo INTO sexo_paciente
    FROM avaliacao_clinica a
    JOIN paciente_titular p ON a.id_paciente = p.id_paciente
    WHERE a.id_avaliacao = NEW.id_avaliacao;

    IF NEW.presente = TRUE THEN
        SELECT peso INTO v_peso
        FROM peso_sintoma
        WHERE id_sintoma = NEW.id_sintoma
        AND sexo = sexo_paciente;
        SET NEW.peso_aplicado = v_peso;
    ELSE
        SET NEW.peso_aplicado = 0;
    END IF;
END //

CREATE TRIGGER classificar_risco
BEFORE UPDATE ON avaliacao_clinica
FOR EACH ROW
BEGIN
    DECLARE sexo_paciente ENUM('Masculino','Feminino');

    SELECT sexo INTO sexo_paciente
    FROM paciente_titular
    WHERE id_paciente = NEW.id_paciente;

    IF (
        (sexo_paciente = 'Masculino' AND NEW.score >= 0.56)
        OR
        (sexo_paciente = 'Feminino' AND NEW.score >= 0.55)
    ) THEN
        SET NEW.classificacao_risco = 'Suspeito';
    ELSE
        SET NEW.classificacao_risco = 'Baixo Risco';
    END IF;
END //

DELIMITER ;

DROP TRIGGER limite_dependentes;