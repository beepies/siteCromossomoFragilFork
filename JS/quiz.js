import { getDados, postData } from "./utils.js";
const defaultQuestions = [ ];


let questions = defaultQuestions;

const historico = [];
let perguntaAtual = 0;
let answers = {};

function addToHistorico(data) {
    historico.unshift(data);
    renderHistorico();
}

function renderHistorico() {
    const list = document.getElementById("historico-list");
    if (!list) return;

    if (historico.length === 0) {
        list.innerHTML = '<p class="historico-vazio">Nenhuma triagem realizada nesta sess�o.</p>';
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
                <span class="historico-id">Triagem #${entry.triagem_id}</span>
                <span class="historico-date">${date}</span>
              </div>
              <div class="historico-score">${positivos} / ${total} sintomas presentes</div>
              <div class="historico-dados">${tags}</div>
            </div>`;
    }).join("");
}

document.addEventListener("DOMContentLoaded", () => {
    const btnIniciar = document.querySelector(".homeStartBtn");
    if (btnIniciar) {
        btnIniciar.addEventListener("click", startQuiz);
    }
});

async function startQuiz() {
    questions = await getDados("data/questions.json");
    perguntaAtual = 0;
    answers = {};
    mostraTab("tab-quiz");
    initQuizUI();
    carregarPergunta();
}

// Inicializa a UI do quiz em uma única iteração (lista de sintomas + hex-dots)
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

// Atualiza lista + hex-dots em uma única passada
function updateProgressUI() {
    questions.forEach((question, index) => {
        const item = document.getElementById(`itemSintoma-${index}`);
        const dot = document.getElementById(`hex-dot-${index}`);

        if (item) {
            item.classList.remove("active", "done");
            if (index < perguntaAtual) item.classList.add("done");
            if (index === perguntaAtual) item.classList.add("active");
            if (index === perguntaAtual) item.scrollIntoView({ block: "nearest", behavior: "smooth" });
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

function setProgresso(percent) {
    const progressoFill = document.getElementById("progressoFill");
    if (progressoFill) progressoFill.style.width = `${percent}%`;
}

function carregarPergunta() {
    const question = questions[perguntaAtual];
    if (!question) return;

    const noQuestao = document.getElementById("no-Questao");
    const tagSintoma = document.getElementById("tagSintoma");
    const questionText = document.getElementById("question-text");
    const questionDesc = document.getElementById("question-desc");
    const optionsContainer = document.getElementById("options-container");
    if (noQuestao) noQuestao.textContent = `Sintoma ${perguntaAtual + 1} de ${questions.length}`;
    if (tagSintoma) tagSintoma.textContent = question.symptom;
    if (questionText) questionText.textContent = question.q;
    if (questionDesc) questionDesc.textContent = question.desc;
    setProgresso((perguntaAtual / questions.length) * 100);
    updateProgressUI();

    const card = document.getElementById("quiz-question-card");
    if (card) {
        card.classList.remove("animarPergunta");
        void card.offsetWidth;
        card.classList.add("animarPergunta");
    }

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
    setProgresso(100);
    updateProgressUI();

    const dadosForBackend = {
        timestamp: new Date().toISOString(),
        triagem_id: Math.floor(Math.random() * 100000),
        dados: answers
    };

    try {
        // Agora você usa a função genérica postData
        const result = await postData("php/quiz.php", dadosForBackend);
        
        addToHistorico(dadosForBackend);
        alert(result.mensagem || "Triagem enviada com sucesso.");
        mostraTab("tab-home");
    } catch (error) {
        console.error("Falha ao enviar triagem:", error);
        alert("Não foi possível enviar a triagem. Tente novamente.");
    }
}
