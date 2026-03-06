const API_URL = 'api/registro.php';

const inputIdAluno = document.getElementById('idAluno');
const btnRegistrar = document.getElementById('btnRegistrar');
const msgBox = document.getElementById('mensagemBox');
const containerAtivo = document.getElementById('alunoAtivoContainer');
const listaFila = document.getElementById('listaFila');
const tbodyRegistros = document.getElementById('tbodyRegistros');

let tempoInterval = null;

function formatarHora(dataString) {
    if (!dataString) return '-';
    const partes = dataString.split(' ');
    if (partes.length === 2) {
        return partes[1].substring(0, 5);
    }
    return dataString;
}

function mostrarMensagem(texto, tipo) {
    msgBox.textContent = texto;
    msgBox.className = `message-box ${tipo}`;
    setTimeout(() => {
        msgBox.classList.add('hidden');
    }, 4000);
}

function iniciarRelogioAtivo(horaSaidaStr) {
    clearInterval(tempoInterval);
    const spanDuracao = document.getElementById('ativoDuracao');
    if (!spanDuracao) return;

    const regex = /^(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})$/;
    const m = horaSaidaStr.match(regex);
    let dataSaida = null;
    if (m) {
        dataSaida = new Date(m[1], m[2] - 1, m[3], m[4], m[5], m[6]);
    } else return;

    tempoInterval = setInterval(() => {
        const agora = new Date();
        const diffMs = agora - dataSaida;
        const diffMins = Math.floor(diffMs / 60000);
        if (diffMins >= 0) spanDuracao.textContent = `${diffMins} min`;
    }, 1000);
}

function atualizarUI(dados) {
    if (dados.ativo && dados.ativo.id) {
        containerAtivo.innerHTML = `
            <div class="ativo-card">
                <div style="margin-bottom: 0.5rem;">
                    <span class="status-indicator busy"></span>
                    <strong>Banheiro Ocupado</strong>
                </div>
                <h3>${dados.ativo.nome} (ID: ${dados.ativo.id_aluno})</h3>
                <p>Saiu às: <strong>${formatarHora(dados.ativo.hora_saida)}</strong></p>
                <p>Duração: <strong id="ativoDuracao">0 min</strong></p>
            </div>
        `;
        containerAtivo.classList.remove('vazio');
        iniciarRelogioAtivo(dados.ativo.hora_saida);
    } else {
        containerAtivo.innerHTML = `
            <div class="status-indicator available"></div>
            <p class="empty-state status-text" style="color: var(--action-color);">Banheiro Livre no momento.</p>
        `;
        containerAtivo.classList.add('vazio');
        clearInterval(tempoInterval);
    }

    listaFila.innerHTML = '';
    if (dados.fila && dados.fila.length > 0) {
        dados.fila.forEach((f, idx) => {
            const li = document.createElement('li');
            li.innerHTML = `<span><strong>${idx + 1}º</strong> - ${f.nome}</span> <span>${formatarHora(f.hora_registro_fila)}</span>`;
            listaFila.appendChild(li);
        });
    } else {
        listaFila.innerHTML = '<li class="empty-state text-center">Ninguém está na fila</li>';
    }

    tbodyRegistros.innerHTML = '';
    if (dados.registros_hoje && dados.registros_hoje.length > 0) {
        dados.registros_hoje.forEach(r => {
            const tr = document.createElement('tr');
            const classeBadge = r.status_alunos === 'CONCLUIDO' ? 'badge-concluido' : 'badge-andamento';
            const statusTexto = r.status_alunos.replace('_', ' ');

            tr.innerHTML = `
                <td><strong>${r.nome}</strong></td>
                <td>${formatarHora(r.hora_saida)}</td>
                <td>${r.hora_retorno ? formatarHora(r.hora_retorno) : '-'}</td>
                <td>${r.duracao_minutos !== null ? r.duracao_minutos + ' min' : '-'}</td>
                <td><span class="badge ${classeBadge}">${statusTexto}</span></td>
            `;
            tbodyRegistros.appendChild(tr);
        });
    } else {
        tbodyRegistros.innerHTML = '<tr><td colspan="5" class="empty-state text-center">Nenhum registro hoje.</td></tr>';
    }
}

async function carregarDados() {
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
        console.error("Erro ao carregar dados:", error);
    }
}

btnRegistrar.addEventListener('click', async () => {
    const id = inputIdAluno.value.trim();
    if (!id) {
        mostrarMensagem('Obrigatório informar o código da chamada.', 'warning');
        return;
    }
    btnRegistrar.disabled = true;
    try {
        const res = await fetch(API_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_aluno: id })
        });

        const textData = await res.text();
        let json;
        try {
            json = JSON.parse(textData);
        } catch (e) {
            console.error("Retorno inválido:", textData);
            mostrarMensagem('Erro de conexão.', 'error');
            btnRegistrar.disabled = false;
            return;
        }

        if (json.status.includes('success')) {
            mostrarMensagem(json.mensagem, json.status === 'success_fila' ? 'warning' : 'success');
            inputIdAluno.value = '';
            carregarDados();
        } else {
            mostrarMensagem(json.mensagem, 'error');
        }

    } catch (error) {
        console.error("Erro no POST:", error);
        mostrarMensagem('Erro de conexão.', 'error');
    }
    btnRegistrar.disabled = false;
    inputIdAluno.focus();
});

inputIdAluno.addEventListener('keypress', (e) => {
    if (e.key === 'Enter') btnRegistrar.click();
});

document.addEventListener('DOMContentLoaded', () => {
    carregarDados();
    setInterval(carregarDados, 10000);
});
