
import mysql.connector
from mysql.connector import errorcode


def setup_database():

    print("\n--- Configuração do Banco Sindrome_X ---")

    db_host = input("Digite o host (Padrão: localhost): ") or "localhost"
    db_port = input("Digite a porta (Padrão: 3306): ") or "3306"
    db_user = input("Digite o usuário (Padrão: root): ") or "root"
    db_pass = input("Digite a senha do MySQL: ")

    try:

        conn = mysql.connector.connect(
            host=db_host,
            port=int(db_port),
            user=db_user,
            password=db_pass
        )

        cursor = conn.cursor()

        # CRIA BANCO

        cursor.execute("CREATE DATABASE IF NOT EXISTS sindrome_x")
        print("Banco sindrome_x criado/verificado.")

        cursor.execute("USE sindrome_x")

        # TABELA PROFISSIONAL SAUDE

        cursor.execute("""

        CREATE TABLE IF NOT EXISTS profissional_saude (

            id_profissional INT AUTO_INCREMENT PRIMARY KEY,

            nome_completo VARCHAR(150) NOT NULL,

            senha_profissional VARCHAR(255) NOT NULL,

            registro_profissional VARCHAR(30) UNIQUE NOT NULL,

            especialidade VARCHAR(100) NOT NULL,

            email VARCHAR(100) UNIQUE,

            telefone VARCHAR(20),

            instituicao VARCHAR(200)

        )

        """)

        print("Tabela profissional_saude criada/verificada.")

        # TABELA PACIENTE TITULAR

        cursor.execute("""

        CREATE TABLE IF NOT EXISTS paciente_titular (

            id_paciente INT AUTO_INCREMENT PRIMARY KEY,

            numero_inscricao VARCHAR(30) UNIQUE NOT NULL,

            nome_completo VARCHAR(150) NOT NULL,

            cpf VARCHAR(14) UNIQUE NOT NULL,

            data_nascimento DATE NOT NULL,

            sexo ENUM('Masculino','Feminino') NOT NULL,

            email VARCHAR(100),

            telefone VARCHAR(20),

            endereco TEXT,

            id_profissional INT NOT NULL,

            id_profissional_atual INT NOT NULL,

            FOREIGN KEY (id_profissional)
            REFERENCES profissional_saude(id_profissional),

            FOREIGN KEY (id_profissional_atual)
            REFERENCES profissional_saude(id_profissional)

        )

        """)

        print("Tabela paciente_titular criada/verificada.")

        # TABELA DEPENDENTE

        cursor.execute("""

        CREATE TABLE IF NOT EXISTS dependente (

            id_dependente INT AUTO_INCREMENT PRIMARY KEY,

            numero_inscricao VARCHAR(30) UNIQUE NOT NULL,

            nome_completo VARCHAR(150) NOT NULL,

            data_nascimento DATE NOT NULL,

            sexo ENUM('Masculino','Feminino') NOT NULL,

            email VARCHAR(100),

            id_titular INT NOT NULL,

            FOREIGN KEY (id_titular)
            REFERENCES paciente_titular(id_paciente)

        )

        """)

        print("Tabela dependente criada/verificada.")

        # TABELA AVALIACAO CLINICA

        cursor.execute("""

        CREATE TABLE IF NOT EXISTS avaliacao_clinica (

            id_avaliacao INT AUTO_INCREMENT PRIMARY KEY,

            id_profissional INT NOT NULL,

            id_paciente INT NULL,

            id_dependente INT NULL,

            data_avaliacao DATETIME NOT NULL,

            score DECIMAL(5,2),

            classificacao_risco ENUM('Baixo Risco','Suspeito'),

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

        )

        """)

        print("Tabela avaliacao_clinica criada/verificada.")

        # TABELA SINTOMA

        cursor.execute("""

        CREATE TABLE IF NOT EXISTS sintoma (

            id_sintoma INT AUTO_INCREMENT PRIMARY KEY,

            nome_sintoma VARCHAR(100) NOT NULL

        )

        """)

        print("Tabela sintoma criada/verificada.")

        # TABELA PESO SINTOMA

        cursor.execute("""

        CREATE TABLE IF NOT EXISTS peso_sintoma (

            id_peso INT AUTO_INCREMENT PRIMARY KEY,

            id_sintoma INT NOT NULL,

            sexo ENUM('Masculino','Feminino') NOT NULL,

            peso DECIMAL(5,2) NOT NULL,

            FOREIGN KEY (id_sintoma)
            REFERENCES sintoma(id_sintoma)

        )

        """)

        print("Tabela peso_sintoma criada/verificada.")

        # TABELA AVALIACAO SINTOMA

        cursor.execute("""

        CREATE TABLE IF NOT EXISTS avaliacao_sintoma (

            id_avaliacao INT,

            id_sintoma INT,

            presente BOOLEAN NOT NULL,

            peso_aplicado DECIMAL(5,2),

            PRIMARY KEY (id_avaliacao, id_sintoma),

            FOREIGN KEY (id_avaliacao)
            REFERENCES avaliacao_clinica(id_avaliacao),

            FOREIGN KEY (id_sintoma)
            REFERENCES sintoma(id_sintoma)

        )

        """)

        print("Tabela avaliacao_sintoma criada/verificada.")

        # TABELA TESTE GENETICO

        cursor.execute("""

        CREATE TABLE IF NOT EXISTS teste_genetico (

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

        )

        """)

        print("Tabela teste_genetico criada/verificada.")

        # REMOVE TRIGGERS ANTIGOS

        cursor.execute("DROP TRIGGER IF EXISTS verificar_idade_titular")
        cursor.execute("DROP TRIGGER IF EXISTS limite_dependentes")
        cursor.execute("DROP TRIGGER IF EXISTS aplicar_peso_sintoma")
        cursor.execute("DROP TRIGGER IF EXISTS classificar_risco")
        cursor.execute("DROP TRIGGER IF EXISTS atualizar_score")

        # TRIGGER IDADE TITULAR

        cursor.execute("""

        CREATE TRIGGER verificar_idade_titular
        BEFORE INSERT ON paciente_titular
        FOR EACH ROW
        BEGIN

            IF TIMESTAMPDIFF(YEAR, NEW.data_nascimento, CURDATE()) < 18 THEN

                SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Titular deve ser maior de 18 anos';

            END IF;

        END

        """)

        print("Trigger verificar_idade_titular criado/verificado.")

        # TRIGGER LIMITE DEPENDENTES

        cursor.execute("""

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

        END

        """)

        print("Trigger limite_dependentes criado/verificado.")

        # TRIGGER APLICAR PESO

        cursor.execute("""

        CREATE TRIGGER aplicar_peso_sintoma
        BEFORE INSERT ON avaliacao_sintoma
        FOR EACH ROW
        BEGIN

            DECLARE sexo_paciente VARCHAR(20);

            DECLARE v_peso DECIMAL(5,2);

            SELECT p.sexo
            INTO sexo_paciente
            FROM avaliacao_clinica a
            JOIN paciente_titular p
                ON a.id_paciente = p.id_paciente
            WHERE a.id_avaliacao = NEW.id_avaliacao;

            IF sexo_paciente IS NULL THEN

                SELECT d.sexo
                INTO sexo_paciente
                FROM avaliacao_clinica a
                JOIN dependente d
                    ON a.id_dependente = d.id_dependente
                WHERE a.id_avaliacao = NEW.id_avaliacao;

            END IF;

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

        END

        """)

        print("Trigger aplicar_peso_sintoma criado/verificado.")

        # TRIGGER CLASSIFICAR RISCO

        cursor.execute("""

        CREATE TRIGGER classificar_risco
        BEFORE UPDATE ON avaliacao_clinica
        FOR EACH ROW
        BEGIN

            DECLARE sexo_paciente VARCHAR(20);

            IF NEW.id_paciente IS NOT NULL THEN

                SELECT sexo
                INTO sexo_paciente
                FROM paciente_titular
                WHERE id_paciente = NEW.id_paciente;

            ELSE

                SELECT sexo
                INTO sexo_paciente
                FROM dependente
                WHERE id_dependente = NEW.id_dependente;

            END IF;

            IF (
                (sexo_paciente = 'Masculino' AND NEW.score >= 0.56)
                OR
                (sexo_paciente = 'Feminino' AND NEW.score >= 0.55)
            ) THEN

                SET NEW.classificacao_risco = 'Suspeito';

            ELSE

                SET NEW.classificacao_risco = 'Baixo Risco';

            END IF;

        END

        """)

        print("Trigger classificar_risco criado/verificado.")

        # TRIGGER ATUALIZAR SCORE

        cursor.execute("""

        CREATE TRIGGER atualizar_score
        AFTER INSERT ON avaliacao_sintoma
        FOR EACH ROW
        BEGIN

            DECLARE total_score DECIMAL(5,2);

            SELECT SUM(peso_aplicado)
            INTO total_score
            FROM avaliacao_sintoma
            WHERE id_avaliacao = NEW.id_avaliacao;

            UPDATE avaliacao_clinica
            SET score = total_score
            WHERE id_avaliacao = NEW.id_avaliacao;

        END

        """)

        print("Trigger atualizar_score criado/verificado.")

        # VERIFICA SE JÁ EXISTEM SINTOMAS

        cursor.execute("SELECT COUNT(*) FROM sintoma")
        total_sintomas = cursor.fetchone()[0]

        if total_sintomas == 0:

            # INSERT SINTOMAS

            cursor.execute("""

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
            ('Face alongada, mandíbula proeminente e/ou orelhas salientes')

            """)

            print("Sintomas inseridos.")

            # INSERT PESOS MASCULINOS

            cursor.execute("""

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
            (12, 'Masculino', 0.29)

            """)

            # INSERT PESOS FEMININOS

            cursor.execute("""

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
            (12, 'Feminino', 0.09)

            """)

            print("Pesos inseridos.")

        else:

            print("Sintomas e pesos já existem.")

        conn.commit()

        print("\n--- Banco configurado com sucesso! ---")

    except mysql.connector.Error as err:

        if err.errno == errorcode.ER_ACCESS_DENIED_ERROR:
            print("Usuário ou senha incorretos.")

        else:
            print(f"Erro: {err}")

    finally:

        if 'conn' in locals() and conn.is_connected():

            cursor.close()
            conn.close()


if __name__ == "__main__":
    setup_database()

