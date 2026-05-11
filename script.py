import mysql.connector
from mysql.connector import errorcode


def setup_databases():
    print("\n--- Automação de Infraestrutura MySQL ---")

    # 1. Inputs dinâmicos para portabilidade
    db_host = input("Digite o host (Padrão: localhost): ") or "localhost"
    db_port = input("Digite a porta (Padrão: 3306): ") or "3306"
    db_user = input("Digite o usuário (Padrão: root): ") or "root"
    db_pass = input("Digite a senha do MySQL: ")

    # 2. Dicionário de Bancos e Tabelas (Escalável)
    projetos = {
        "meu_site": [
            """
            CREATE TABLE IF NOT EXISTS usuarios (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nome VARCHAR(100) NOT NULL,
                email VARCHAR(100) NOT NULL UNIQUE,
                senha VARCHAR(255) NOT NULL,
                nascimento DATE,
                data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
            """
        ],
        "projeto_futuro_teste": [
            "CREATE TABLE IF NOT EXISTS logs (id INT AUTO_INCREMENT PRIMARY KEY, evento TEXT)"
        ]
    }

    try:
        # 3. Conexão Inicial ao Servidor
        conn = mysql.connector.connect(
            host=db_host,
            port=int(db_port),
            user=db_user,
            password=db_pass
        )
        cursor = conn.cursor()

        for db_name, tables in projetos.items():
            try:
                # Criação do Banco de Dados
                cursor.execute(f"CREATE DATABASE IF NOT EXISTS {db_name}")
                print(f"✔ Banco '{db_name}' verificado/criado.")

                # Seleciona o Banco para criar as tabelas dentro dele
                conn.database = db_name

                for table_sql in tables:
                    cursor.execute(table_sql)
                print(f"  ✔ Tabelas de '{db_name}' verificadas/criadas.")

            except mysql.connector.Error as err:
                print(f"❌ Erro ao processar banco {db_name}: {err}")

        conn.commit()
        print("\n--- Setup finalizado com sucesso! ---")

    except mysql.connector.Error as err:
        if err.errno == errorcode.ER_ACCESS_DENIED_ERROR:
            print("❌ Erro: Usuário ou senha incorretos.")
        elif err.errno == errorcode.ER_BAD_DB_ERROR:
            print("❌ Erro: Banco de dados não existe.")
        else:
            print(f"❌ Erro inesperado: {err}")
    finally:
        if 'conn' in locals() and conn.is_connected():
            cursor.close()
            conn.close()


if __name__ == "__main__":
    setup_databases()