document.addEventListener("DOMContentLoaded", () => {
    // Tenta pegar turma da URL
    const urlParams = new URLSearchParams(window.location.search);
    const turmaId = urlParams.get('turma_id');
    const turmaNome = urlParams.get('turma_nome');

    if (turmaNome) {
        // Mostra o nome da turma na UI se existir elemento
        const h1 = document.querySelector('header h1');
        if (h1) h1.innerText = `Controle de Banheiro - ${turmaNome}`;
    }

    carregarDados();
    setInterval(carregarDados, 10000); // Atualiza painel a cada 10s

    const form = document.getElementById("formRegistro");
    const idInput = document.getElementById("id_aluno");
    const preview = document.getElementById("nomeAlunoPreview");

    idInput.addEventListener("input", async (e) => {
        const numChamada = e.target.value;
        const urlParams = new URLSearchParams(window.location.search);
        const turmaId = urlParams.get('turma_id');

        if (numChamada.length > 0 && turmaId) {
            try {
                const res = await fetch(`api/buscar_aluno.php?numero=${numChamada}&id_turma=${turmaId}`);
                const json = await res.json();
                if (json.status === 'success') {
                    preview.innerHTML = `Identificado: <strong>${json.aluno.nome}</strong>`;
                } else {
                    preview.innerHTML = `<span style='color:red;'>Aluno não encontrado nesta turma</span>`;
                }
            } catch (e) { }
        } else {
            preview.innerHTML = "";
        }
    });

    form.addEventListener("submit", async (e) => {
        e.preventDefault();
        const numChamada = idInput.value;
        if (!numChamada) return;

        try {
            const urlParams = new URLSearchParams(window.location.search);
            const turmaId = urlParams.get('turma_id');

            if (!turmaId) {
                alert("Erro: Nenhuma turma selecionada na URL.");
                return;
            }

            const payload = {
                numero_chamada: numChamada,
                id_turma: turmaId
            };

            const res = await fetch("api/registro.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(payload)
            });
            const result = await res.json();
            const msgBox = document.getElementById("mensagem");

            if (result.status === "success") {
                msgBox.innerHTML = `<span class="status-concluido">✔ ${result.message}</span>`;
                idInput.value = "";
                preview.innerHTML = "";
            } else {
                msgBox.innerHTML = `<span style="color:red;">✖ ${result.message}</span>`;
            }

            carregarDados();
            setTimeout(() => { msgBox.innerHTML = ""; }, 5000);
        } catch (error) {
            console.error("Erro na requisição:", error);
        }
    });

    // --- LÓGICA DE RIPPLE EXTRAÍDA PARA UI.JS ---
});

async function carregarDados() {
    try {
        const urlParams = new URLSearchParams(window.location.search);
        const turmaId = urlParams.get('turma_id');
        const query = turmaId ? `?id_turma=${turmaId}` : '';

        const req = await fetch(`api/registro.php${query}`);
        const res = await req.json();

        // Render ativo
        const ativoArea = document.getElementById("ativoArea");
        if (res.ativo) {
            ativoArea.innerHTML = `
                <p style="margin:0;"><strong>${res.ativo.nome}</strong> (Nº Chamada: ${res.ativo.numero_chamada})</p>
                <p style="margin:5px 0 0 0; color:#64748b; font-size:0.9rem;">Saiu às: ${res.ativo.hora_saida}</p>
            `;
        } else {
            ativoArea.innerHTML = "<p>Ninguém fora da sala no momento.</p>";
        }

        // Render fila
        const filaArea = document.getElementById("filaArea");
        if (res.fila && res.fila.length > 0) {
            let html = "<ol style='margin-top:0; padding-left:20px;'>";
            res.fila.forEach((f, i) => {
                let delay = i * 0.05;
                html += `<li class="animate-slide-up" style='margin-bottom:8px; animation-delay: ${delay}s;'>
                    <strong>${f.nome}</strong> 
                    <div style='color:#64748b; font-size:0.85rem;'>Aguardando desde ${f.hora_entrada_fila}</div>
                </li>`;
            });
            html += "</ol>";
            filaArea.innerHTML = html;
        } else {
            filaArea.innerHTML = "<p>Ninguém na fila.</p>";
        }

        // Render hoje
        const hojeArea = document.getElementById("hojeArea");
        if (res.registros && res.registros.length > 0) {
            let html = `<table><tr><th>Aluno</th><th>Saída</th><th>Retorno</th><th>Status</th></tr>`;
            res.registros.forEach((r, i) => {
                const isFora = r.status_alunos === 'EM_ANDAMENTO';
                const statusStr = isFora ? `<span class="status-andamento">FORA DA SALA</span>` : `<span class="status-concluido">CONCLUÍDO (${r.tempo_gasto}m)</span>`;
                const ret = r.hora_retorno ? r.hora_retorno : '-';

                let delay = i * 0.05;
                const rowStyle = isFora ? `background-color: #fffbeb; animation-delay: ${delay}s;` : `animation-delay: ${delay}s;`;

                html += `<tr class="animate-slide-up" style="${rowStyle}">
                    <td><strong>${r.numero_chamada} - ${r.nome}</strong></td>
                    <td>${r.hora_saida}</td>
                    <td>${ret}</td>
                    <td>${statusStr}</td>
                </tr>`;
            });
            html += `</table>`;
            hojeArea.innerHTML = html;
        } else {
            hojeArea.innerHTML = "<p>Nenhum registro de uso do banheiro hoje.</p>";
        }
    } catch (e) {
        console.error("Erro ao carregar os dados:", e);
    }
}
