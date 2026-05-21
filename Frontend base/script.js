// login
document.getElementById("loginForm").addEventListener("submit", function (e) {
    e.preventDefault();
    const btn = e.target.querySelector("button");
    btn.innerText = "Autenticando...";
    setTimeout(() => {
        document.getElementById("telaLogin").style.display = "none";
        document.getElementById("dashboard-screen").style.display = "flex";
    }, 1000);
});

// navegação site
function mostraTab(tabId) {
    document.querySelectorAll(".tab-content").forEach(tab => tab.style.display = "none");
    document.getElementById(tabId).style.display = "block";
    document.getElementById("userDropdown").classList.remove("active");}

function abrirDropdown() {
    document.getElementById("userDropdown").classList.toggle("active");}
window.onclick = (e) => {
    if (!e.target.closest(".user-menu")) {
        document.getElementById("userDropdown").classList.remove("active");
 }};

// sintomas
// notas::
//  id       → chave enviada ao backend
//  symptom  → nome do sintoma exibido na lista lateral e na tag da pergunta
//  q        → substituir pela pergunta real
//  desc     → substituir pela descrição real
//
const questions = [
    {
        id: "atraso_fala",
        symptom: "Atraso na fala",
        q: "Lorem ipsum dolor sit amet consectetur adipiscing elit?",
        desc: "Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua."},
    {
        id: "dif_aprendizagem",
        symptom: "Dificuldades de aprendizagem",
        q: "Sed do eiusmod tempor incididunt ut labore dolore?",
        desc: "Ut labore et dolore magna aliqua enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi aliquip."},
    {
        id: "deficit_atencao",
        symptom: "Déficit de atenção",
        q: "Exercitation ullamco laboris nisi ut aliquip ex commodo?",
        desc: "Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur."
    },
    {
        id: "def_intelectual",
        symptom: "Deficiência intelectual",
        q: "Duis aute irure dolor reprehenderit voluptate velit cillum?",
        desc: "Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est."
    },
    {
        id: "hiperatividade",
        symptom: "Hiperatividade",
        q: "Excepteur sint occaecat cupidatat non proident deserunt?",
        desc: "Deserunt mollit anim id est laborum sed perspiciatis unde omnis iste natus error sit voluptatem accusantium."},
    {
        id: "comp_agressivo",
        symptom: "Comportamento agressivo",
        q: "Natus error sit voluptatem accusantium doloremque laudantium?",
        desc: "Totam rem aperiam eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta."
    },
    {
        id: "contato_visual",
        symptom: "Evita contato visual",
        q: "Nemo enim ipsam voluptatem quia voluptas sit aspernatur?",
        desc: "Neque porro quisquam est qui dolorem ipsum quia dolor sit amet consectetur adipisci velit sed quia."
    },
    {
        id: "contato_fisico",
        symptom: "Evita contato físico",
        q: "Ut enim ad minima veniam quis nostrum exercitationem?",
        desc: "Quis autem vel eum iure reprehenderit qui in ea voluptate velit esse quam nihil molestiae consequatur."},
    {
        id: "mov_repetitivos",
        symptom: "Movimentos repetitivos e rítmicos",
        q: "At vero eos et accusamus et iusto odio dignissimos?",
        desc: "Nam libero tempore cum soluta nobis est eligendi optio cumque nihil impedit quo minus id quod maxime."
    },
    {
        id: "hipermobilidade",
        symptom: "Hipermobilidade articular",
        q: "Temporibus autem quibusdam et aut officiis debitis rerum?",
        desc: "Quas molestias excepturi sint occaecati cupiditate non provident similique sunt in culpa qui officia deserunt."
    },
    {
        id: "macroorquidia",
        symptom: "Macroorquidia",
        q: "Itaque earum rerum hic tenetur sapiente delectus reiciendis?",
        desc: "Ut aut reiciendis voluptatibus maiores alias consequatur aut perferendis doloribus asperiores repellat omnis."
    },
    {
        id: "face_orelhas",
        symptom: "Face alongada / orelhas salientes",
        q: "Sed ut perspiciatis unde omnis iste natus error voluptatem?",
        desc: "Accusantium doloremque laudantium totam rem aperiam eaque ipsa quae ab illo inventore veritatis et quasi."
    }
];
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
}