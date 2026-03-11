document.addEventListener("DOMContentLoaded", () => {
    const urlParams = new URLSearchParams(window.location.search);
    const turmaNome = urlParams.get('turma_nome');

    if (turmaNome) {
        const h1 = document.querySelector('header h1');
        if (h1) h1.innerText = `Histórico - ${turmaNome}`;
    }

    const dataAtual = new Date();
    let mesAtual = dataAtual.getMonth();
    let anoAtual = dataAtual.getFullYear();

    const meses = ["Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro"];
    const diasSemana = ["Dom", "Seg", "Ter", "Qua", "Qui", "Sex", "Sáb"];

    const mesAnoLabel = document.getElementById("mesAnoAtual");
    const grid = document.getElementById("calendarioGrid");
    const labelSelection = document.getElementById("dataSelecionadaLabel");

    function renderizarListaSemana() {
        diasSemana.forEach(d => {
            const div = document.createElement("div");
            div.className = "dia-semana";
            div.innerText = d;
            grid.appendChild(div);
        });
    }

    function renderizarCalendario(mes, ano) {
        grid.innerHTML = "";
        mesAnoLabel.innerText = `${meses[mes]} ${ano}`;
        renderizarListaSemana();

        const primeiroDiaMes = new Date(ano, mes, 1).getDay();
        const numDiasMes = new Date(ano, mes + 1, 0).getDate();

        // Blocos vazios (dias antes do dia 1 do mês atual)
        for (let i = 0; i < primeiroDiaMes; i++) {
            const div = document.createElement("div");
            div.className = "dia vazio";
            grid.appendChild(div);
        }

        // Blocos numéricos do mês
        const hojeH = new Date();
        for (let dia = 1; dia <= numDiasMes; dia++) {
            const div = document.createElement("div");
            div.className = "dia animate-slide-up";
            div.innerText = dia;
            // Delay dinâmico baseado no dia para montar o calendário em cascata suave
            const delay = dia * 0.02;
            div.style.animationDelay = `${delay}s`;

            // Marca o dia atual (hoje visual)
            if (dia === hojeH.getDate() && mes === hojeH.getMonth() && ano === hojeH.getFullYear()) {
                div.classList.add("hoje");
                div.classList.add("selecionado");
            }

            // Evento de clique para o usuário
            div.addEventListener("click", () => {
                document.querySelectorAll(".dia").forEach(d => d.classList.remove("selecionado"));
                div.classList.add("selecionado");

                // Conversão YYYY-MM-DD
                const mStr = String(mes + 1).padStart(2, '0');
                const dStr = String(dia).padStart(2, '0');
                const isoDate = `${ano}-${mStr}-${dStr}`;

                labelSelection.innerText = `${dStr}/${mStr}/${ano}`;
                buscarRegistros(isoDate);
            });

            grid.appendChild(div);
        }
    }

    document.getElementById("btnAnterior").addEventListener("click", () => {
        mesAtual--;
        if (mesAtual < 0) {
            mesAtual = 11;
            anoAtual--;
        }
        renderizarCalendario(mesAtual, anoAtual);
    });

    document.getElementById("btnProximo").addEventListener("click", () => {
        mesAtual++;
        if (mesAtual > 11) {
            mesAtual = 0;
            anoAtual++;
        }
        renderizarCalendario(mesAtual, anoAtual);
    });

    // Run do calendário
    renderizarCalendario(mesAtual, anoAtual);

    // Auto-carrega logs do dia de hoje (ao inicializar página)
    const mStrInit = String(dataAtual.getMonth() + 1).padStart(2, '0');
    const dStrInit = String(dataAtual.getDate()).padStart(2, '0');
    labelSelection.innerText = `${dStrInit}/${mStrInit}/${dataAtual.getFullYear()}`;
    buscarRegistros(`${dataAtual.getFullYear()}-${mStrInit}-${dStrInit}`);
});

async function buscarRegistros(isoDate) {
    const area = document.getElementById("resultadoArea");
    area.innerHTML = "<p>Carregando histórico...</p>";

    try {
        const urlParams = new URLSearchParams(window.location.search);
        const turmaId = urlParams.get('turma_id');
        const query = turmaId ? `&id_turma=${turmaId}` : '';

        const req = await fetch(`api/calendario.php?data=${isoDate}${query}`);
        const res = await req.json();

        if (res.status === "success" && res.registros.length > 0) {
            let html = `<table><tr><th>Aluno</th><th>Horários</th><th>Duração</th></tr>`;
            res.registros.forEach((r, idx) => {
                // Previne null se aluno não tiver retornado no dia pesquisado
                const retStr = r.hora_retorno ? r.hora_retorno.split(" ")[1] : '<span class="status-andamento">Aberto/Pendente</span>';
                const saidaStr = r.hora_saida.split(" ")[1];
                const durStr = r.tempo_gasto !== null ? `<span style="color:var(--status-concluido);">${r.tempo_gasto}m</span>` : '-';

                let delay = idx * 0.05;
                html += `<tr class="animate-slide-up" style="animation-delay: ${delay}s">
                    <td><strong>${r.nome}</strong><br><small>ID: ${r.id_alunos}</small></td>
                    <td>${saidaStr} as ${retStr}</td>
                    <td><strong>${durStr}</strong></td>
                </tr>`;
            });
            html += `</table>`;
            area.innerHTML = html;
        } else {
            area.innerHTML = "<p>Sem movimentação neste dia.</p>";
        }
    } catch (e) {
        area.innerHTML = "<p style='color:red;'>Erro de comunicação com a API ao tentar buscar o histórico (Offline).</p>";
    }
}
