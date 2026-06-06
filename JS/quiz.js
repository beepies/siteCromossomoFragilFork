import { getDados, postData, getData, verificarSessao } from "./utils.js"; // Importado getData

let questions = [];
const historico = [];
let perguntaAtual = 0;
let answers = {};
let numeroInscricaoPacienteAtual = "";

document.addEventListener("DOMContentLoaded", async () => {
    await verificarSessao();
    
    // Configura o botão da Home
    const btnIniciar = document.querySelector(".homeStartBtn");
    if (btnIniciar) {
        btnIniciar.addEventListener("click", () => {
            // Reseta a tela de busca ao entrar nela
            resetarTelaBusca();
            window.mostraTab("tab-identificacao");
        });
    }

    // Configura o botão de "Verificar"
    const btnBuscar = document.getElementById("btnBuscarPacienteTriagem");
    if (btnBuscar) {
        btnBuscar.addEventListener("click", buscarPacienteParaTriagem);
    }

    // Configura o botão de "Avançar" para o Quiz
    const btnAvancar = document.getElementById("btnAvancarParaQuiz");
    if (btnAvancar) {
        btnAvancar.addEventListener("click", startQuiz);
    }
});

// Função para buscar o paciente no banco via PHP antes do Quiz
async function buscarPacienteParaTriagem() {
    const inputInscricao = document.getElementById("numero_inscricao_triagem");
    const msgErro = document.getElementById("msgErroIdentificacao");
    const containerDados = document.getElementById("dadosPacienteTriagem");
    const btnAvancar = document.getElementById("btnAvancarParaQuiz");
    
    const valor = inputInscricao.value.trim();

    if (!valor) {
        msgErro.innerText = "Por favor, digite o número de inscrição.";
        msgErro.style.display = "block";
        return;
    }

    msgErro.style.display = "none";

    try {
        // Faz o GET enviando a inscrição pela URL (O getData já injeta o Token de Acesso do médico)
        const resposta = await getData(`php/buscar_paciente_triagem.php?inscricao=${encodeURIComponent(valor)}`);

        if (resposta.status === 'sucesso') {
            // Exibe os dados retornados do banco na tela
            document.getElementById("nomePacienteConfirmado").innerText = resposta.dados.nome_completo;
            document.getElementById("cpfPacienteConfirmado").innerText = resposta.dados.cpf;
            
            containerDados.style.display = "block";
            
            // Libera o botão de avançar para o Quiz
            numeroInscricaoPacienteAtual = valor; 
            btnAvancar.disabled = false;
            btnAvancar.style.opacity = "1";
            btnAvancar.style.cursor = "pointer";
        } else {
            // Caso o paciente não exista ou não pertença a este médico
            containerDados.style.display = "none";
            msgErro.innerText = resposta.mensagem;
            msgErro.style.display = "block";
            btnAvancar.disabled = true;
            btnAvancar.style.opacity = "0.5";
            btnAvancar.style.cursor = "not-allowed";
        }
    } catch (erro) {
        console.error("Erro ao verificar paciente:", erro);
        msgErro.innerText = "Erro de conexão ao validar o paciente.";
        msgErro.style.display = "block";
    }
}

// Reseta o estado visual da tela de busca
function resetarTelaBusca() {
    document.getElementById("numero_inscricao_triagem").value = "";
    document.getElementById("dadosPacienteTriagem").style.display = "none";
    document.getElementById("msgErroIdentificacao").style.display = "none";
    
    const btnAvancar = document.getElementById("btnAvancarParaQuiz");
    btnAvancar.disabled = true;
    btnAvancar.style.opacity = "0.5";
    btnAvancar.style.cursor = "not-allowed";
}

async function startQuiz() {
    questions = await getDados("data/questions.json");
    perguntaAtual = 0;
    answers = {};
    
    window.mostraTab("tab-quiz");
    initQuizUI();
    carregarPergunta();
}

function initQuizUI() {
    const list = document.getElementById("listaSintomas");
    const hexa = document.getElementById("hexaProgresso");
    if (list) list.innerHTML = "";
    if (hexa) hexa.innerHTML = "";

    questions.forEach((question, index) => {
        if (list) {
            const li = document.createElement("li");
            li.className = "itemSintoma";
            li.id = `itemSintoma-${index}`;
            li.textContent = question.symptom;
            list.appendChild(li);
        }
        if (hexa) {
            const dot = document.createElement("span");
            dot.className = "hex-dot";
            dot.id = `hex-dot-${index}`;
            hexa.appendChild(dot);
        }
    });

    const counter = document.getElementById("counterSintomas");
    if (counter) counter.textContent = `0 / ${questions.length}`;
}

function updateProgressUI() {
    questions.forEach((_, index) => {
        const item = document.getElementById(`itemSintoma-${index}`);
        const dot = document.getElementById(`hex-dot-${index}`);

        if (item) {
            item.classList.remove("active", "done");
            if (index < perguntaAtual) item.classList.add("done");
            if (index === perguntaAtual) {
                item.classList.add("active");
                item.scrollIntoView({ block: "nearest", behavior: "smooth" });
            }
        }

        if (dot) {
            dot.classList.remove("done", "active");
            if (index < perguntaAtual) dot.classList.add("done");
            if (index === perguntaAtual) dot.classList.add("active");
        }
    });

    const counter = document.getElementById("counterSintomas");
    if (counter) counter.textContent = `${Math.min(perguntaAtual, questions.length)} / ${questions.length}`;
}

function carregarPergunta() {
    const question = questions[perguntaAtual];
    if (!question) return;

    document.getElementById("no-Questao").textContent = `Sintoma ${perguntaAtual + 1} de ${questions.length}`;
    document.getElementById("tagSintoma").textContent = question.symptom;
    document.getElementById("question-text").textContent = question.q;
    document.getElementById("question-desc").textContent = question.desc;
    
    const percent = (perguntaAtual / questions.length) * 100;
    const progressoFill = document.getElementById("progressoFill");
    if (progressoFill) progressoFill.style.width = `${percent}%`;
    
    updateProgressUI();

    const card = document.getElementById("quiz-question-card");
    if (card) {
        card.classList.remove("animarPergunta");
        void card.offsetWidth; // Força reflow do layout para reiniciar animação CSS
        card.classList.add("animarPergunta");
    }

    const optionsContainer = document.getElementById("options-container");
    if (!optionsContainer) return;
    optionsContainer.innerHTML = "";

    const opts = [
        { label: "SINTOMA PRESENTE", valor: 1, classe: "btn-1" },
        { label: "SINTOMA AUSENTE", valor: 0, classe: "btn-0" }
    ];

    opts.forEach(opt => {
        const btn = document.createElement("button");
        btn.className = `opcao-btn ${opt.classe}`;
        btn.textContent = opt.label;
        btn.onclick = () => {
            answers[question.id] = opt.valor;
            perguntaAtual += 1;

            if (perguntaAtual < questions.length) {
                carregarPergunta();
            } else {
                finishQuiz();
            }
        };
        optionsContainer.appendChild(btn);
    });
}

async function finishQuiz() {
    const progressoFill = document.getElementById("progressoFill");
    if (progressoFill) progressoFill.style.width = "100%";
    updateProgressUI();

    // DRY: Enviando o número de inscrição capturado na aba intermediária
    const dadosForBackend = {
        numero_inscricao: numeroInscricaoPacienteAtual,
        timestamp: new Date().toISOString(),
        triagem_id: Math.floor(Math.random() * 100000),
        dados: answers
    };

    try {
        const result = await postData("php/quiz.php", dadosForBackend);
        
        historico.unshift(dadosForBackend);
        renderHistorico();

        alert(result.mensagem || "Triagem gravada com sucesso.");
        
        // Limpa o formulário de inscrição para a próxima consulta
        const inputInscricao = document.getElementById("numero_inscricao_triagem");
        if (inputInscricao) inputInscricao.value = "";
        
        window.mostraTab("tab-home");
    } catch (error) {
        console.error("Falha ao enviar triagem:", error);
        alert("Erro na comunicação com o servidor ao salvar triagem.");
    }
}

function renderHistorico() {
    const list = document.getElementById("historico-list");
    if (!list) return;

    if (historico.length === 0) {
        list.innerHTML = '<p class="historico-vazio">Nenhuma triagem realizada nesta sessão.</p>';
        return;
    }

    list.innerHTML = historico.map(entry => {
        const date = new Date(entry.timestamp).toLocaleString("pt-BR");
        const positivos = Object.values(entry.dados).filter(v => v === 1).length;
        const total = Object.keys(entry.dados).length;
        
        const tags = Object.entries(entry.dados).map(([id, val]) => {
            const question = questions.find(q => q.id === id);
            const nome = question ? question.symptom : id;
            const cls = val ? "tag-presente" : "tag-ausente";
            const txt = val ? "Presente" : "Ausente";
            return `<span class="historico-tag ${cls}">${nome}: ${txt}</span>`;
        }).join("");

        return `
            <div class="historico-item">
              <div class="historico-header">
                <span class="historico-id">Triagem #${entry.triagem_id} (Paciente: ${entry.numero_inscricao})</span>
                <span class="historico-date">${date}</span>
              </div>
              <div class="historico-score">${positivos} / ${total} sintomas presentes</div>
              <div class="historico-dados">${tags}</div>
            </div>`;
    }).join("");
}