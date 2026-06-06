// Removemos todos os fallbacks antigos e lógicas duplicadas de quiz

// Navegação do painel - Disponibilizado globalmente
window.mostraTab = function(tabId) {
    document.querySelectorAll(".tab-content").forEach(tab => tab.style.display = "none");
    const alvo = document.getElementById(tabId);
    if (alvo) alvo.style.display = "block";
    
    const dropdown = document.getElementById("userDropdown");
    if (dropdown) dropdown.classList.remove("active");
};

// Controle do Menu Dropdown do Usuário
window.abrirDropdown = function() {
    const dropdown = document.getElementById("userDropdown");
    if (dropdown) dropdown.classList.toggle("active");
};

// Fecha o dropdown se clicar fora dele
window.addEventListener("click", (e) => {
    if (!e.target.closest(".user-menu")) {
        const dropdown = document.getElementById("userDropdown");
        if (dropdown) dropdown.classList.remove("active");
    }
});

// login fallback removed: preserve the page when the login block is absent
/*const loginForm = document.getElementById("loginForm");
if (loginForm) {
    loginForm.addEventListener("submit", function (e) {
        e.preventDefault();
        const btn = e.target.querySelector("button");
        btn.innerText = "Autenticando...";
        setTimeout(() => {
            const loginTela = document.getElementById("telaLogin");
            if (loginTela) loginTela.style.display = "none";
            document.getElementById("dashboard-screen").style.display = "flex";
        }, 1000);
    });
}

// navegação site
function mostraTab(tabId) {
    document.querySelectorAll(".tab-content").forEach(tab => tab.style.display = "none");
    document.getElementById(tabId).style.display = "block";
    document.getElementById("userDropdown").classList.remove("active");}

function abrirDropdown() {
    document.getElementById("userDropdown").classList.toggle("active");
}

window.onclick = (e) => {
    if (!e.target.closest(".user-menu")) {
        document.getElementById("userDropdown").classList.remove("active");
    }
};
// historico de consultas (memoria da sessao)
const historico = [];
function addToHistorico(data) {
    historico.unshift(data); // mais recente primeiro
    renderHistorico();}

function renderHistorico() {
    const list = document.getElementById("historico-list");
    if (!list) return;
    if (historico.length === 0) {
        list.innerHTML = '<p class="historico-vazio">Nenhuma triagem realizada nesta sessão.</p>';
return;}
    list.innerHTML = historico.map(entry => {
        const date      = new Date(entry.timestamp).toLocaleString("pt-BR");
        const positivos = Object.values(entry.dados).filter(v => v === 1).length;
        const total     = Object.keys(entry.dados).length;
        const tags = Object.entries(entry.dados).map(([id, val]) => {
        const q    = questions.find(q => q.id === id);
        const nome = q ? q.symptom : id;
        const cls  = val ? "tag-presente" : "tag-ausente";
         const txt  = val ? "Presente" : "Ausente";
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
let perguntaAtual = 0;
let answers  = {};
// start quiz
function startQuiz() {
perguntaAtual = 0;
answers  = {};
mostraTab("tab-quiz");
buildListaSintoma();
controiHexaProgresso();
carregarPergunta();}
// lista de sintomas na lateral
function buildListaSintoma() {
    const list = document.getElementById("listaSintomas");
    list.innerHTML = "";
    questions.forEach((q, i) => {
        const li = document.createElement("li");
        li.className = "itemSintoma";
        li.id = `itemSintoma-${i}`;
        li.textContent = q.symptom;
        list.appendChild(li);
    });}
// atualizando estados da lista 
function atualizaListaSintomaEstado() {
    questions.forEach((_, i) => {
        const item = document.getElementById(`itemSintoma-${i}`);
        if (!item) return;
        item.classList.remove("active", "done");
        if (i < perguntaAtual)  item.classList.add("done");
        if (i === perguntaAtual) item.classList.add("active");
        // scroll automático para manter o item ativo visível na nav
        if (i === perguntaAtual) {
            item.scrollIntoView({ block: "nearest", behavior: "smooth" });
        }
    });
    // contador  "X / 12"
    const counter = document.getElementById("counterSintomas");
    if (counter) counter.textContent = `${perguntaAtual} / ${questions.length}`;}


// mini hexágonos no topo do card indicando progresso
function controiHexaProgresso() {
    const container = document.getElementById("hexaProgresso");
    if (!container) return;
    container.innerHTML = "";

    // mostra no máximo 12 dots (um por sintoma)
    questions.forEach((_, i) => {
        const dot = document.createElement("span");
        dot.className = "hex-dot";
        dot.id = `hex-dot-${i}`;
        container.appendChild(dot);
    });
}

function hexagonoProgresso() {questions.forEach((_, i) => {
 const dot = document.getElementById(`hex-dot-${i}`);
 if (!dot) return;
 dot.classList.remove("done", "active");
if (i < perguntaAtual)  dot.classList.add("done");
      if (i === perguntaAtual) dot.classList.add("active");
    });
}

// carregar a pergunta atual
function carregarPergunta() {
    const q = questions[perguntaAtual];
    document.getElementById("no-Questao").textContent =
        `Sintoma ${perguntaAtual + 1} de ${questions.length}`;
    document.getElementById("tagSintoma").textContent   = q.symptom;
    document.getElementById("question-text").textContent = q.q;
    document.getElementById("question-desc").textContent = q.desc;

    // barra de progresso
    const pct = (perguntaAtual / questions.length) * 100;
    document.getElementById("progressoFill").style.width = `${pct}%`;
    atualizaListaSintomaEstado();
    hexagonoProgresso();

    // animação de entrada da pergunta
    const card = document.getElementById("quiz-question-card");
    if (card) {
        card.classList.remove("animarPergunta");
    void card.offsetWidth; // força reflow para reiniciar animação
        card.classList.add("animarPergunta");}
    // botoes binário
    const container = document.getElementById("options-container");
    container.innerHTML = "";
    const opts = [
        { label: "SINTOMA PRESENTE", valor: 1, classe: "btn-1" },
    { label: "SINTOMA AUSENTE",  valor: 0, classe: "btn-0" } ];

    opts.forEach(opt => {
        const btn = document.createElement("button");
        btn.className = `opcao-btn ${opt.classe}`;
        btn.textContent = opt.label;
        btn.onclick = () => {
            answers[q.id] = opt.valor; // 0 ou 1
            perguntaAtual++;

            if (perguntaAtual < questions.length) {
               carregarPergunta();
            } else {
                finishQuiz();
            }
        };

        container.appendChild(btn);
    });
}

// finalizando o quiz
function finishQuiz() {
    document.getElementById("progressoFill").style.width = "100%";
    atualizaListaSintomaEstado();
    hexagonoProgresso();
    // "dados" contém { id_sintoma: 0 | 1 } para cada sintoma
    const dadosForBackend = {
        timestamp:   new Date().toISOString(),
       triagem_id:  Math.floor(Math.random() * 100000),
        dados: answers
    };
    console.log("dados para o backend:", JSON.stringify(dadosForBackend, null, 2));
addToHistorico(dadosForBackend);
    alert("dados convertidos em formato binário");
    mostraTab("tab-home");
}*/