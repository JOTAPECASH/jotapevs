let dadosProjeto = {}; let meuGrafico;
let periodoAtual = '1m'; 

const nomeContaEl = document.getElementById('nome-conta');
const saldoEl = document.getElementById('saldo-display');
const saldoPercentualEl = document.getElementById('saldo-percentual');
const stakeEl = document.getElementById('stake-display');
const riscoValorEl = document.getElementById('risco-valor-display');
const statusUsuarioEl = document.getElementById('status-usuario');
const apostaRegistradaEl = document.getElementById('aposta-registrada-display');
const tickerWrapperEl = document.getElementById('ticker-wrapper');
const btnStart = document.getElementById('btn-buy');
const btnPare = document.getElementById('btn-sell');
const aumentarStakeBtn = document.getElementById('aumentar-stake');
const diminuirStakeBtn = document.getElementById('diminuir-stake');
const accountSwitcherEl = document.getElementById('account-switcher');
const actionsPanel = document.querySelector('.actions-panel');
const timeframeButtons = document.querySelectorAll('.timeframe-filters button');

function atualizarTicker(resultados) {
    if (!tickerWrapperEl || !resultados || resultados.length === 0) return;
    tickerWrapperEl.innerHTML = '';
    const criarItem = (resultado) => {
        const item = document.createElement('div');
        item.className = 'ticker-item';
        const logo = document.createElement('img');
        logo.src = 'olho.png';
        logo.alt = 'Logo';
        logo.className = 'ticker-logo';
        const content = document.createElement('div');
        content.className = 'ticker-item-content';
        const title = document.createElement('span');
        title.className = 'ticker-title';
        title.innerText = resultado.titulo;
        const profit = document.createElement('span');
        profit.className = 'ticker-profit';
        if (resultado.lucro >= 0) {
            profit.innerText = `+$${resultado.lucro.toFixed(2)}`;
            profit.classList.add('profit-positivo');
        } else {
            profit.innerText = `-$${Math.abs(resultado.lucro).toFixed(2)}`;
            profit.classList.add('profit-negativo');
        }
        content.appendChild(title);
        content.appendChild(profit);
        item.appendChild(logo);
        item.appendChild(content);
        return item;
    };
    resultados.forEach(res => tickerWrapperEl.appendChild(criarItem(res)));
    resultados.forEach(res => tickerWrapperEl.appendChild(criarItem(res)));
}

async function fetchDados() { 
    try { 
        const response = await fetch(`get_user_data.php?periodo=${periodoAtual}`); 
        const novosDados = await response.json(); 
        dadosProjeto = novosDados; 

        if (!meuGrafico && document.getElementById('meuGrafico')) {
            inicializarGrafico(novosDados);
        } else {
            atualizarGrafico(); 
        }

        atualizarTela(); 
        atualizarTicker(dadosProjeto.ultimos_resultados);
    } catch (error) { 
        console.error("Erro ao buscar dados:", error); 
    } 
}

function atualizarTela() { 
    if (!dadosProjeto.saldo && dadosProjeto.saldo !== 0) return; 
    nomeContaEl.innerText = (dadosProjeto.conta_ativa === 'real') ? 'Conta Real' : 'Conta Demo'; 
    saldoEl.innerText = `$${dadosProjeto.saldo.toFixed(2)}`; 
    stakeEl.innerText = `${dadosProjeto.stake}%`; 
    let valorDoRisco = (dadosProjeto.saldo * dadosProjeto.stake) / 100; 
    riscoValorEl.innerText = `$${valorDoRisco.toFixed(2)}`; 

    if (dadosProjeto.conta_ativa === 'real') { 
        statusUsuarioEl.style.display = 'block'; 
        actionsPanel.style.display = 'flex'; 
        if (dadosProjeto.status === 'ativo') { 
            statusUsuarioEl.innerText = "● Ativo"; 
            statusUsuarioEl.className = 'status-ativo'; 
        } else { 
            statusUsuarioEl.innerText = "● Pausado"; 
            statusUsuarioEl.className = 'status-pausado'; 
        } 
    } else { 
        statusUsuarioEl.style.display = 'none'; 
        actionsPanel.style.display = 'none'; 
    } 

    // --- LÓGICA DE EXIBIÇÃO CORRIGIDA ---
    if (dadosProjeto.tem_aposta_aberta) {
        let ganhoPotencial = dadosProjeto.aposta_ganho || 0;
        apostaRegistradaEl.innerText = `APOSTA REGISTRADA +$${ganhoPotencial.toFixed(2)}`;
        apostaRegistradaEl.classList.add('blinking-text');
        saldoPercentualEl.innerText = ''; // Limpa o percentual enquanto há aposta aberta
    } else {
        apostaRegistradaEl.innerText = '';
        apostaRegistradaEl.classList.remove('blinking-text');
        
        // Volta a exibir o percentual de lucro/perda
        const percent = dadosProjeto.evolucao_percentual; 
        if (percent > 0) { 
            saldoPercentualEl.innerText = `+${percent.toFixed(2)}%`; 
            saldoPercentualEl.className = 'percentual-change percent-positivo'; 
        } else if (percent < 0) { 
            saldoPercentualEl.innerText = `${percent.toFixed(2)}%`; 
            saldoPercentualEl.className = 'percentual-change percent-negativo'; 
        } else { 
            // Mostra 0.00% se não houver lucro nem perda
            saldoPercentualEl.innerText = `+0.00%`; 
            saldoPercentualEl.className = 'percentual-change';
        }
    }
}

function inicializarGrafico(dadosIniciais) { 
    const ctx = document.getElementById('meuGrafico').getContext('2d'); 
    meuGrafico = new Chart(ctx, { 
        type: 'line', 
        data: { 
            labels: dadosIniciais.grafico_labels, 
            datasets: [{ 
                label: 'Evolução do Lucro', 
                data: dadosIniciais.grafico_datapoints, 
                borderColor: '#00e091', 
                backgroundColor: 'rgba(0, 224, 145, 0.1)', 
                borderWidth: 2, 
                pointRadius: 3, 
                pointBackgroundColor: '#ffffff', 
                fill: true, 
                tension: 0.4 
            }] 
        }, 
        options: { 
            responsive: true, 
            maintainAspectRatio: false,
            layout: { padding: { right: 10 } },
            plugins: { 
                legend: { display: false },
                zoom: { 
                    pan: { enabled: true, mode: 'x' },
                    zoom: { wheel: { enabled: true }, pinch: { enabled: true }, mode: 'x' } 
                } 
            }, 
            scales: { 
                x: { grid: { color: 'rgba(255, 255, 255, 0.1)' }, ticks: { color: '#8a91a0', display: false } }, 
                y: { 
                    grid: { color: 'rgba(255, 255, 255, 0.1)' }, 
                    ticks: { color: '#8a91a0' },
                    beginAtZero: true 
                } 
            } 
        } 
    }); 
}

function atualizarGrafico() { 
    if (!meuGrafico || !dadosProjeto.grafico_datapoints) return; 
    meuGrafico.data.labels = dadosProjeto.grafico_labels; 
    meuGrafico.data.datasets[0].data = dadosProjeto.grafico_datapoints; 
    meuGrafico.update(); 
}

function salvarStake() { fetch('salvar_dados.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ stake: dadosProjeto.stake, tipo_conta: dadosProjeto.conta_ativa }) }).then(r=>r.json()).then(d=>console.log('Stake salvo!',d));}
function salvarStatus(novoStatus) { fetch('atualizar_status.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ status: novoStatus }) }).then(r=>r.json()).then(d=>{if(d.status==='success'){fetchDados();}});}
function mudarConta(novaConta) { document.body.style.opacity = '0.5'; fetch('mudar_conta.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ nova_conta: novaConta }) }).then(r=>r.json()).then(d=>{if(d.status==='success'){window.location.reload();}});}

document.addEventListener('DOMContentLoaded', () => {
    fetchDados(); 
    setInterval(fetchDados, 5000); 
    
    if (aumentarStakeBtn) { aumentarStakeBtn.addEventListener('click', () => { if (dadosProjeto.stake < 100) { dadosProjeto.stake += 10; } atualizarTela(); salvarStake(); }); }
    if (diminuirStakeBtn) { diminuirStakeBtn.addEventListener('click', () => { if (dadosProjeto.stake > 10) { dadosProjeto.stake -= 10; } atualizarTela(); salvarStake(); }); }
    if (btnStart) { btnStart.addEventListener('click', () => { salvarStatus('ativo'); }); }
    if (btnPare) { btnPare.addEventListener('click', () => { salvarStatus('pausado'); }); }
    if (accountSwitcherEl) { accountSwitcherEl.addEventListener('click', () => { const novaConta = (dadosProjeto.conta_ativa === 'demo') ? 'real' : 'demo'; mudarConta(novaConta); }); }
    if (timeframeButtons) {
        timeframeButtons.forEach(button => {
            button.addEventListener('click', () => {
                timeframeButtons.forEach(btn => btn.classList.remove('active'));
                button.classList.add('active');
                periodoAtual = button.getAttribute('data-periodo');
                fetchDados();
            });
        });
    }
});