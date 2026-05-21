$json = ConvertTo-Json @{
    nome = "teste"
    Registro_profissional = "123"
    Especialidade = "test"
    email = "newemail@test.com"
    telefone = "111"
    Instituicao = "test"
    senha = "123"
}

$response = Invoke-WebRequest -Uri 'http://localhost/Site/php/cadastro.php' -Method Post -ContentType 'application/json' -Body $json

$response.Content
