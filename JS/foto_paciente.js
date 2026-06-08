import { getData } from './utils.js';
const params = new URLSearchParams(window.location.search);
const inscricao = params.get('inscricao');
const nome = params.get('nome');
const token = localStorage.getItem('token_acesso');
if (nome) document.getElementById('nomePaciente').textContent = nome;
// carrega foto existente
async function carregarFoto() {
    if (!inscricao || !token) return;
    const response = await fetch(`php/foto_paciente.php?inscricao=${encodeURIComponent(inscricao)}`, {
        headers: { 'Authorization': token } });
    if (response.ok) {
        const blob = await response.blob();
        const url = URL.createObjectURL(blob);
        const img = document.getElementById('fotoPaciente');
        img.src = url;
        img.style.display = 'block';
        document.getElementById('fotoPlaceholder').style.display = 'none';
    }
}
carregarFoto();

// preview do arquivo selecionado
const inputFoto = document.getElementById('inputFoto');
const botaoSalvar = document.getElementById('botaoSalvar');
const previewNome = document.getElementById('previewNome');

inputFoto.addEventListener('change', function() {
if (this.files[0]) {
    previewNome.textContent = this.files[0].name;
    botaoSalvar.style.display = 'block';
}});

async function comprimir_imagem(file, maxLargura = 800, qualidade = 0.8) {
    return new Promise(resolve => {
        const reader = new FileReader();
        reader.onload = e => {
            const img = new Image();
            img.onload = () => {
                const canvas = document.createElement('canvas');
                let largura = img.width;
                let altura = img.height;
                if (largura > maxLargura) {
                    altura = Math.round(altura * maxLargura / largura);
                    largura = maxLargura;
                }
                canvas.width = largura;
                canvas.height = altura;
                canvas.getContext('2d').drawImage(img, 0, 0, largura, altura);
                canvas.toBlob(blob => resolve(blob), 'image/jpeg', qualidade);
            };
            img.src = e.target.result;
        };
        reader.readAsDataURL(file);
    });
}

// upload
botaoSalvar.addEventListener('click', async function() {
const file = inputFoto.files[0];


if (!file || !inscricao) return;
const formData = new FormData();
const fotoComprimida = await comprimir_imagem(file);
formData.append('foto', fotoComprimida, 'foto.jpg');
formData.append('numero_inscricao', inscricao);
const msg = document.getElementById('msgStatus');
botaoSalvar.textContent = 'Salvando...';

const response = await fetch('php/upload_foto.php', {
    method: 'POST',
    headers: { 'Authorization': token },
    body: formData});
const data = await response.json();
botaoSalvar.textContent = 'Salvar foto';
if (data.status === 'sucesso') { msg.className = 'msg sucesso';
    msg.textContent = 'Foto salva com sucesso!';
    // atualiza a imagem
    const img = document.getElementById('fotoPaciente');
    const reader = new FileReader();
    reader.onload = e => {
        img.src = e.target.result;
        img.style.display = 'block';
        document.getElementById('fotoPlaceholder').style.display = 'none';};
    reader.readAsDataURL(file);
} else {
    msg.className = 'msg erro';
    msg.textContent = data.mensagem || 'Erro ao salvar foto.'; }});