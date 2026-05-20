CREATE DATABASE sindrome_x;
USE sindrome_x;
-- CRIAÇÃO DAS TABELAS


CREATE TABLE profissional_saude (
    id_profissional INT AUTO_INCREMENT PRIMARY KEY,

    nome_completo VARCHAR(150) NOT NULL,

    registro_profissional VARCHAR(30)
    UNIQUE NOT NULL,

    especialidade VARCHAR(100) NOT NULL,

    email VARCHAR(100) UNIQUE,
    telefone VARCHAR(20),

    instituicao VARCHAR(200)
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


-- Titular deve ser maior de 18 anos


DELIMITER //

CREATE TRIGGER verificar_idade_titular
BEFORE INSERT ON paciente_titular
FOR EACH ROW
BEGIN

    IF TIMESTAMPDIFF(YEAR, NEW.data_nascimento, CURDATE()) < 18 THEN

        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Titular deve ser maior de 18 anos';

    END IF;

END //

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




-- INSERÇÃO DE DADOS


INSERT INTO profissional_saude
(
    nome_completo,
    registro_profissional,
    especialidade
)
VALUES
(
    'João Silva',
    'CRM12345',
    'Neurologia'
);


INSERT INTO sintoma (nome_sintoma)
VALUES
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
('Face alongada, mandíbula proeminente e/ou orelhas salientes');


-- PESOS MASCULINOS

INSERT INTO peso_sintoma
(id_sintoma, sexo, peso)
VALUES
(1, 'Masculino', 0.14),
(2, 'Masculino', 0.18),
(3, 'Masculino', 0.17),
(4, 'Masculino', 0.32),
(5, 'Masculino', 0.12),
(6, 'Masculino', 0.01),
(7, 'Masculino', 0.06),
(8, 'Masculino', 0.04),
(9, 'Masculino', 0.17),
(10, 'Masculino', 0.19),
(11, 'Masculino', 0.26),
(12, 'Masculino', 0.29);

-- PESOS FEMININOS

INSERT INTO peso_sintoma
(id_sintoma, sexo, peso)
VALUES
(1, 'Feminino', 0.01),
(2, 'Feminino', 0.28),
(3, 'Feminino', 0.12),
(4, 'Feminino', 0.20),
(5, 'Feminino', 0.04),
(6, 'Feminino', 0.02),
(7, 'Feminino', 0.08),
(8, 'Feminino', 0.07),
(9, 'Feminino', 0.05),
(10, 'Feminino', 0.04),
(12, 'Feminino', 0.09);


INSERT INTO paciente_titular
(
    numero_inscricao,
    nome_completo,
    cpf,
    data_nascimento,
    sexo,
    id_profissional,
    id_profissional_atual
)
VALUES
(
    'PAC001',
    'Carlos Souza',
    '123.456.789-00',
    '1990-03-10',
    'Masculino',
    1,
    1
);


INSERT INTO avaliacao_clinica
(
    id_profissional,
    id_paciente,
    data_avaliacao,
    classificacao_risco
)
VALUES
(
    1,
    1,
    '2026-05-20 14:30:00',
    'Suspeito'
);


INSERT INTO avaliacao_sintoma
(
    id_avaliacao,
    id_sintoma,
    presente
)
VALUES
(1, 1, TRUE),
(1, 2, TRUE),
(1, 3, FALSE),
(1, 4, TRUE),
(1, 5, FALSE),
(1, 6, FALSE),
(1, 7, TRUE),
(1, 8, FALSE),
(1, 9, TRUE),
(1, 10, FALSE),
(1, 11, TRUE),
(1, 12, TRUE);

-- CONSULTA FINAL

SELECT
    p.nome_completo AS paciente,

    p.sexo,

    pr.nome_completo AS profissional,

    a.id_avaliacao,

    a.data_avaliacao,

    a.classificacao_risco,

    s.nome_sintoma,

    avs.presente

FROM avaliacao_clinica a

JOIN paciente_titular p
    ON a.id_paciente = p.id_paciente

JOIN profissional_saude pr
    ON a.id_profissional = pr.id_profissional

JOIN avaliacao_sintoma avs
    ON a.id_avaliacao = avs.id_avaliacao

JOIN sintoma s
    ON avs.id_sintoma = s.id_sintoma;