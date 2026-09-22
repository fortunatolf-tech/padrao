/**
 * Scripts Interativos do Sistema de Votação dos Padrões COMARA
 */

document.addEventListener('DOMContentLoaded', function () {

    // 1. MÁSCARA E VALIDAÇÃO DE CPF (URNA FASE 3)
    const cpfInput = document.getElementById('cpfVotacao');
    if (cpfInput) {
        cpfInput.addEventListener('input', function (e) {
            let v = e.target.value.replace(/\D/g, '');
            if (v.length > 11) v = v.substring(0, 11);
            if (v.length > 9) {
                v = v.replace(/(\d{3})(\d{3})(\d{3})(\d{1,2})/, '$1.$2.$3-$4');
            } else if (v.length > 6) {
                v = v.replace(/(\d{3})(\d{3})(\d{1,3})/, '$1.$2.$3');
            } else if (v.length > 3) {
                v = v.replace(/(\d{3})(\d{1,3})/, '$1.$2');
            }
            e.target.value = v;
        });
    }

    // 2. CONTADOR E VALIDAÇÃO RIGOROSA DA FASE 2 (EXATAMENTE 6 POR CATEGORIA)
    const formFase2 = document.getElementById('formFase2');
    if (formFase2) {
        const categorias = ['graduado', 'praca', 'sppf', 'sptf'];
        const btnSalvar = document.getElementById('btnSalvarFase2');

        function atualizarContadores() {
            let todosValidos = true;

            categorias.forEach(cat => {
                const checks = formFase2.querySelectorAll(`input[name="selecao[${cat}][]"]:checked`);
                const total = checks.length;
                const badge = document.getElementById(`badge-count-${cat}`);

                if (badge) {
                    badge.textContent = `${total} / 6 selecionados`;
                    if (total === 6) {
                        badge.className = 'badge bg-success fs-6';
                    } else if (total < 6) {
                        badge.className = 'badge bg-warning text-dark fs-6';
                        todosValidos = false;
                    } else {
                        badge.className = 'badge bg-danger fs-6';
                        todosValidos = false;
                    }
                }
            });

            if (btnSalvar) {
                btnSalvar.disabled = !todosValidos;
                if (todosValidos) {
                    btnSalvar.classList.remove('btn-secondary');
                    btnSalvar.classList.add('btn-primary');
                } else {
                    btnSalvar.classList.remove('btn-primary');
                    btnSalvar.classList.add('btn-secondary');
                }
            }
        }

        formFase2.addEventListener('change', function (e) {
            if (e.target.type === 'checkbox' && e.target.name.startsWith('selecao[')) {
                const catMatch = e.target.name.match(/selecao\[(.*?)\]/);
                if (catMatch) {
                    const cat = catMatch[1];
                    const checks = formFase2.querySelectorAll(`input[name="selecao[${cat}][]"]:checked`);
                    if (checks.length > 6) {
                        e.target.checked = false;
                        alert('Atenção: O regulamento permite selecionar EXATAMENTE 6 candidatos por categoria. Desmarque um candidato antes de selecionar outro.');
                    }
                }
                atualizarContadores();
            }
        });

        atualizarContadores();
    }

    // 3. SELEÇÃO VISUAL NA URNA (FASE 3)
    const urnaForm = document.getElementById('formUrna');
    if (urnaForm) {
        urnaForm.querySelectorAll('.urna-card-cand').forEach(card => {
            card.addEventListener('click', function () {
                const radio = this.querySelector('input[type="radio"]');
                if (radio) {
                    radio.checked = true;
                    const cat = radio.name;
                    urnaForm.querySelectorAll(`input[name="${cat}"]`).forEach(r => {
                        const parent = r.closest('.urna-card-cand');
                        if (parent) parent.classList.remove('selected');
                    });
                    this.classList.add('selected');
                }
            });
        });
    }

    // 4. PREENCHIMENTO RÁPIDO DO MODAL DE TACF
    const modalTacf = document.getElementById('modalTacf');
    if (modalTacf) {
        modalTacf.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const candId = button.getAttribute('data-candidato-id');
            const candNome = button.getAttribute('data-candidato-nome');
            const candNota = button.getAttribute('data-candidato-nota');

            document.getElementById('tacf_candidato_id').value = candId;
            document.getElementById('tacf_candidato_nome').textContent = candNome;
            document.getElementById('tacf_nota').value = candNota || '5.00';
        });
    }

    // 5. MODAL DE INABILITAÇÃO FASE 4
    const modalExclusao = document.getElementById('modalExclusao');
    if (modalExclusao) {
        modalExclusao.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const candId = button.getAttribute('data-candidato-id');
            const candNome = button.getAttribute('data-candidato-nome');

            document.getElementById('excluir_candidato_id').value = candId;
            document.getElementById('excluir_candidato_nome').textContent = candNome;
        });
    }

    // 6. INICIALIZAÇÃO DE POPOVERS E TOOLTIPS (BOOTSTRAP 5)
    const popoverTriggerList = document.querySelectorAll('[data-bs-toggle="popover"]');
    [...popoverTriggerList].forEach(el => {
        new bootstrap.Popover(el, {
            html: true,
            sanitize: false
        });
    });

    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    [...tooltipTriggerList].forEach(el => new bootstrap.Tooltip(el));
});
