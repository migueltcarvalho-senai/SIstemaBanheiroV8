const API_URL = 'api/dashboard.php';

const statTotalSaidas = document.getElementById('statTotalSaidas');
const statTotalAlunos = document.getElementById('statTotalAlunos');
const statTempoMedio = document.getElementById('statTempoMedio');
const statTempoTotal = document.getElementById('statTempoTotal');

const tbodyAlunos = document.getElementById('tbodyAlunos');
const listaRanking = document.getElementById('listaRanking');

// Formata minutos em "X h Y m" ou "Y m"
function formatarTempo(minutosTotais) {
    if (!minutosTotais || minutosTotais == 0) return '0 m';
    const minutos = Math.floor(minutosTotais);
    if (minutos < 60) return `${minutos} m`;
    const h = Math.floor(minutos / 60);
    const m = minutos % 60;
    return `${h} h ${m} m`;
}

function atualizarUI(dados) {
    // 1. Estatísticas Gerais
    if (dados.estatisticas) {
        statTotalSaidas.textContent = dados.estatisticas.total_saidas || 0;
        statTotalAlunos.textContent = dados.estatisticas.total_alunos_distintos || 0;
        statTempoMedio.textContent = formatarTempo(dados.estatisticas.tempo_medio);
        statTempoTotal.textContent = formatarTempo(dados.estatisticas.tempo_total);
    }

    // 2. Tabela de Alunos que Saíram
    tbodyAlunos.innerHTML = '';
    if (dados.alunos && dados.alunos.length > 0) {
        dados.alunos.forEach(aluno => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td><strong>${aluno.nome}</strong></td>
                <td>${aluno.qtde_saidas}</td>
                <td>${formatarTempo(aluno.tempo_total)}</td>
            `;
            tbodyAlunos.appendChild(tr);
        });
    } else {
        tbodyAlunos.innerHTML = '<tr><td colspan="3" class="empty-state text-center">Nenhuma saída registrada hoje.</td></tr>';
    }

    // 3. Ranking de Demorados
    listaRanking.innerHTML = '';
    if (dados.ranking && dados.ranking.length > 0) {
        dados.ranking.forEach((aluno, index) => {
            const li = document.createElement('li');

            // Cria um badge especial para o Top 3
            let badgeClass = '';
            if (index === 0) badgeClass = 'ranking-1';
            else if (index === 1) badgeClass = 'ranking-2';
            else if (index === 2) badgeClass = 'ranking-3';

            li.innerHTML = `
                <span>
                    <span class="ranking-badge ${badgeClass}">${index + 1}</span>
                    <strong>${aluno.nome}</strong>
                </span>
                <span style="color: var(--warning-text); font-weight: 600;">
                    ${formatarTempo(aluno.tempo_total)}
                </span>
            `;
            listaRanking.appendChild(li);
        });
    } else {
        listaRanking.innerHTML = '<li class="empty-state text-center">Nenhum dado para o ranking.</li>';
    }
}

async function carregarDashboard() {
    try {
        const res = await fetch(API_URL);
        const textData = await res.text();
        let dados;
        try {
            dados = JSON.parse(textData);
        } catch (e) {
            console.error("Erro ao fazer parse do JSON:", textData);
            return;
        }
        atualizarUI(dados);
    } catch (error) {
        console.error("Erro ao carregar dashboard:", error);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    carregarDashboard();
    // Atualiza o dashboard a cada 15 segundos
    setInterval(carregarDashboard, 15000);
});
