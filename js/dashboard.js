document.addEventListener("DOMContentLoaded", () => {
    // Tenta pegar nome da turma da URL
    const urlParams = new URLSearchParams(window.location.search);
    const turmaNome = urlParams.get('turma_nome');

    if (turmaNome) {
        // Mostra o nome da turma na UI
        const h1 = document.querySelector('header h1');
        if (h1) h1.innerText = `Estatísticas Diárias - ${turmaNome}`;
    }

    carregarDashboard();
    setInterval(carregarDashboard, 15000); // Atualiza a cada 15 segundos
});

function formatTime(minutos) {
    if (!minutos || isNaN(minutos)) return "0m";
    const m = Math.round(minutos);
    if (m < 60) return `${m}m`;
    const h = Math.floor(m / 60);
    const rest = m % 60;
    return `${h}h ${rest}m`;
}

async function carregarDashboard() {
    try {
        const urlParams = new URLSearchParams(window.location.search);
        const turmaId = urlParams.get('turma_id');
        const query = turmaId ? `?id_turma=${turmaId}` : '';

        const req = await fetch(`api/dashboard.php${query}`);
        const res = await req.json();

        if (res.status === "success") {
            // Popula KPIs
            const est = res.estatisticas;
            document.getElementById("totalSaidas").innerText = est.total_saidas || 0;
            document.getElementById("totalAlunos").innerText = est.total_alunos_distintos || 0;
            document.getElementById("tempoMedio").innerText = formatTime(est.tempo_medio);
            document.getElementById("tempoTotal").innerText = formatTime(est.tempo_total_gasto);

            // Popula Tabelas
            const ranking = res.ranking;
            const freqArea = document.getElementById("frequenciaArea");
            const rankArea = document.getElementById("rankingArea");

            if (ranking && ranking.length > 0) {
                // Tabela de Frequência (ordena por vezes)
                let freqSort = [...ranking].sort((a, b) => b.frequencia - a.frequencia);
                let htmlFreq = `<table><tr><th>Aluno</th><th>Saídas</th></tr>`;
                freqSort.forEach((r, i) => {
                    let delay = i * 0.05;
                    htmlFreq += `<tr class="animate-slide-up" style="animation-delay: ${delay}s">
                        <td><strong>${r.nome}</strong> <br><small>ID: ${r.id_alunos}</small></td>
                        <td><span style="font-size:1.1rem; font-weight:bold; color:var(--azul-marinho);">${r.frequencia}x</span></td>
                    </tr>`;
                });
                htmlFreq += `</table>`;
                freqArea.innerHTML = htmlFreq;

                // Tabela de Ranking de Tempo (ordena por tempo total gasto)
                let tempSort = [...ranking].sort((a, b) => b.tempo_acumulado - a.tempo_acumulado);
                let htmlRank = `<table><tr><th>Ranking</th><th>Aluno</th><th>Tempo Acumulado</th></tr>`;
                tempSort.forEach((r, idx) => {
                    let badge = '';
                    if (idx === 0) badge = '🥇 1º';
                    else if (idx === 1) badge = '🥈 2º';
                    else if (idx === 2) badge = '🥉 3º';
                    else badge = `${idx + 1}º`;

                    let delay = idx * 0.05;
                    htmlRank += `<tr class="animate-slide-up" style="animation-delay: ${delay}s">
                        <td><span style="font-size:1.2rem;">${badge}</span></td>
                        <td>${r.nome}</td>
                        <td><strong style="color:var(--status-andamento); font-size:1.1rem;">${formatTime(r.tempo_acumulado)}</strong></td>
                    </tr>`;
                });
                htmlRank += `</table>`;
                rankArea.innerHTML = htmlRank;

            } else {
                freqArea.innerHTML = "<p>Nenhum registro concluído encontrado para o dia de hoje.</p>";
                rankArea.innerHTML = "<p>Nenhum registro concluído encontrado para o dia de hoje.</p>";
            }
        }
    } catch (e) {
        console.error("Erro ao carregar as estatísticas:", e);
    }
}
