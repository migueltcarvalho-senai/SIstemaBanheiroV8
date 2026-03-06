document.addEventListener("DOMContentLoaded", () => {
    carregarDados();
    setInterval(carregarDados, 10000); // Atualiza painel a cada 10s

    const form = document.getElementById("formRegistro");
    const idInput = document.getElementById("id_aluno");
    const preview = document.getElementById("nomeAlunoPreview");

    // Verifica se os elementos do formulário existem (Página Início)
    if (idInput && preview) {
        idInput.addEventListener("input", async (e) => {
            const id = e.target.value;
            if(id.length > 0) {
                try {
                    const res = await fetch(`api/buscar_aluno.php?id=${id}`);
                    const json = await res.json();
                    if(json.status === 'success') {
                        preview.innerHTML = `Identificado: <strong>${json.aluno.nome}</strong>`;
                    } else {
                        preview.innerHTML = `<span style='color:red;'>Nenhum aluno com este ID</span>`;
                    }
                } catch(e) {}
            } else {
                preview.innerHTML = "";
            }
        });
    }

    if (form && idInput) {
        form.addEventListener("submit", async (e) => {
            e.preventDefault();
            const id = idInput.value;
            if (!id) return;

            try {
                const res = await fetch("api/registro.php", {
                    method: "POST",
                    headers: {"Content-Type": "application/json"},
                    body: JSON.stringify({id_alunos: id})
                });
                const result = await res.json();
                const msgBox = document.getElementById("mensagem");
                
                if (result.status === "success") {
                    if (msgBox) msgBox.innerHTML = `<span class="status-concluido">✔ ${result.message}</span>`;
                    idInput.value = "";
                    if (preview) preview.innerHTML = "";
                } else {
                    if (msgBox) msgBox.innerHTML = `<span style="color:red;">✖ ${result.message}</span>`;
                }

                carregarDados();
                if (msgBox) {
                    setTimeout(() => { msgBox.innerHTML = ""; }, 5000);
                }
            } catch (error) {
                console.error("Erro na requisição:", error);
            }
        });
    }

    // --- LÓGICA DE RIPPLE EXTRAÍDA PARA UI.JS ---
});

async function carregarDados() {
    try {
        const req = await fetch("api/registro.php");
        const res = await req.json();

        // Render ativo
        const ativoArea = document.getElementById("ativoArea");
        if (res.ativo) {
            ativoArea.innerHTML = `
                <p style="margin:0;"><strong>${res.ativo.nome}</strong> (ID: ${res.ativo.id_alunos})</p>
                <p style="margin:5px 0 0 0; color:#64748b; font-size:0.9rem;">Saiu às: ${res.ativo.hora_saida}</p>
            `;
        } else {
            ativoArea.innerHTML = "<p>Ninguém fora da sala no momento.</p>";
        }

        // Render fila
        const filaArea = document.getElementById("filaArea");
        if (res.fila && res.fila.length > 0) {
            let html = "<ol style='margin-top:0; padding-left:20px;'>";
            res.fila.forEach(f => {
                html += `<li style='margin-bottom:8px;'>
                    <strong>${f.nome}</strong> 
                    <div style='color:#64748b; font-size:0.85rem;'>ID: ${f.id_alunos} | Aguardando desde ${f.hora_entrada_fila}</div>
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
            res.registros.forEach(r => {
                const isFora = r.status_alunos === 'EM_ANDAMENTO';
                const statusStr = isFora ? `<span class="status-andamento">FORA DA SALA</span>` : `<span class="status-concluido">CONCLUÍDO (${r.tempo_gasto}m)</span>`;
                const ret = r.hora_retorno ? r.hora_retorno : '-';
                // tr hover se for o ativo
                const rowStyle = isFora ? "background-color: #fffbeb;" : "";
                
                html += `<tr style="${rowStyle}">
                    <td><strong>${r.nome}</strong><br><small>ID: ${r.id_alunos}</small></td>
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
