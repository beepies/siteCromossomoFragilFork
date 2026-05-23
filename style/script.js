// login fallback removed: preserve the page when the login block is absent
const loginForm = document.getElementById("loginForm");
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

// O quiz e os dados do backend foram movidos para JS/quiz.js