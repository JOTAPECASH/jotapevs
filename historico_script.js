// --- ELEMENTOS DA TELA ---
const historyTableBodyEl = document.getElementById('history-table-body');
const accountSwitcherEl = document.getElementById('account-switcher');

// --- FUNÇÕES ---

// Busca os dados do histórico e atualiza a tabela
async function fetchHistorico() {
    try {
        const response = await fetch('get_history_data.php');
        const historico = await response.json();
        
        // Limpa a tabela
        historyTableBodyEl.innerHTML = ''; 

        if (historico && historico.length > 0) {
            historico.forEach(item => {
                const tr = document.createElement('tr');
                
                // Formata o resultado
                let resultadoHtml = '<span>-</span>';
                if (item.status === 'ganhou') {
                    resultadoHtml = `<span class="lucro">+${item.lucro_perda.toFixed(2)}</span>`;
                } else if (item.status === 'perdeu') {
                    resultadoHtml = `<span class="perda">${item.lucro_perda.toFixed(2)}</span>`;
                }

                // Formata o status
                let statusClass = item.status === 'aberta' ? 'aguardando' : item.status;
                let statusText = item.status === 'aberta' ? 'Aguardando' : item.status.charAt(0).toUpperCase() + item.status.slice(1);

                // Preenche a linha da tabela
                tr.innerHTML = `
                    <td>${item.titulo}</td>
                    <td>$${parseFloat(item.valor_investido).toFixed(2)}</td>
                    <td>${resultadoHtml}</td>
                    <td><span class="status ${statusClass}">${statusText}</span></td>
                `;
                historyTableBodyEl.appendChild(tr);
            });
        } else {
            historyTableBodyEl.innerHTML = '<tr><td colspan="4" style="text-align:center; color:#8a91a0;">Nenhum histórico encontrado para esta conta.</td></tr>';
        }

    } catch (error) {
        console.error("Erro ao buscar histórico:", error);
    }
}

// Função para mudar de conta (igual à da outra página)
function mudarConta(novaConta) {
    document.body.style.opacity = '0.5'; 
    fetch('mudar_conta.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ nova_conta: novaConta }) })
    .then(r=>r.json()).then(d=>{if(d.status==='success'){window.location.reload();}});
}


// --- INICIALIZAÇÃO ---
document.addEventListener('DOMContentLoaded', () => {
    // Busca os dados pela primeira vez
    fetchHistorico();
    
    // Configura o "Polling": busca os dados a cada 5 segundos
    setInterval(fetchHistorico, 5000); 

    // Adiciona o evento de clique no seletor de conta
    if(accountSwitcherEl) {
        // Precisamos pegar a conta ativa do HTML para o primeiro clique
        const contaAtual = document.getElementById('nome-conta').innerText.includes('Real') ? 'real' : 'demo';
        accountSwitcherEl.addEventListener('click', () => {
            const novaConta = (contaAtual === 'demo') ? 'real' : 'demo';
            mudarConta(novaConta);
        });
    }
});