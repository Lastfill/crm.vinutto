/**
 * Ponte: painel cadastro.html (site) → API do CRM
 * Mantém marca d'água / etapas do cadastro e grava no banco unificado.
 *
 * Requer login no CRM (mesmo usuário) ou use as credenciais do loginOverlay.
 * Configure:
 *   window.VINUTTO_API_URL = 'https://seu-dominio/api.php';
 */
(function () {
  const API_URL = window.VINUTTO_API_URL || window.API_URL || 'api.php';
  let sessionUser = null;
  let sessionToken = null;

  function status(msg, isErr) {
    const el = document.getElementById('statusSalvar');
    if (el) {
      el.textContent = msg || '';
      el.style.color = isErr ? '#b91c1c' : '';
    }
  }

  async function apiJson(action, payload, method) {
    method = method || 'POST';
    let url = `${API_URL}?action=${encodeURIComponent(action)}`;
    const headers = { 'Content-Type': 'application/json' };
    if (sessionToken) headers['Authorization'] = 'Bearer ' + sessionToken;
    const opts = { method, headers };
    if (method === 'GET') {
      const qs = new URLSearchParams(payload || {});
      if ([...qs].length) url += '&' + qs.toString();
    } else {
      const body = { ...(payload || {}) };
      if (sessionToken) body.token = sessionToken;
      opts.body = JSON.stringify(body);
    }
    const res = await fetch(url, opts);
    return res.json().catch(() => ({ success: false, error: 'Resposta inválida' }));
  }

  window.salvarImovelNoCRM = async function salvarImovelNoCRM() {
    if (!sessionUser) {
      status('Faça login no painel antes de salvar no CRM.', true);
      return;
    }

    const titulo = (document.getElementById('f_titulo')?.value || '').trim();
    if (!titulo) {
      status('Título é obrigatório.', true);
      return;
    }

    const categoria = document.getElementById('f_categoria')?.value || 'prontos';
    const bairro = document.getElementById('f_bairro')?.value || '';
    const preco = document.getElementById('f_preco')?.value || '';
    const condicao = document.getElementById('f_condicao')?.value || '';
    const badge = document.getElementById('f_badge')?.value || '';
    const area = document.getElementById('f_area')?.value || '';
    const quartos = document.getElementById('f_quartos')?.value || '';
    const vagas = document.getElementById('f_vagas')?.value || '';
    const desc = document.getElementById('f_desc')?.value || '';
    const slug = (document.getElementById('f_id')?.value || '').trim();

    // Tags selecionadas (diferenciais)
    let diferenciais = [];
    document.querySelectorAll('#etiqWrap .etiq.ativa, #etiqWrap .etiq.selected, #etiqWrap .tag.on').forEach((el) => {
      const t = (el.textContent || '').trim();
      if (t) diferenciais.push(t);
    });
    // Fallback: se o cadastro.js expõe estado global
    if (!diferenciais.length && Array.isArray(window.tagsSelecionadas)) {
      diferenciais = window.tagsSelecionadas.slice();
    }

    // Fotos já processadas (caminhos ou dataURL) — se cadastro.js guardar em window.fotosLista
    const fotosPaths = Array.isArray(window.fotosLista)
      ? window.fotosLista
      : Array.isArray(window.fotos)
        ? window.fotos
        : [];

    // Extrai número de quartos/vagas quando possível
    const parseNum = (s) => {
      const m = String(s || '').match(/\d+/);
      return m ? parseInt(m[0], 10) : 0;
    };

    // Valor venda: tenta parsear "R$ 950.000"
    let valorVenda = null;
    const digits = String(preco).replace(/[^\d,]/g, '').replace(',', '.');
    if (digits && !/consulte/i.test(preco)) {
      const n = parseFloat(digits.replace(/\./g, '').replace(',', '.'));
      // se formato BR com pontos de milhar
      const onlyDigits = String(preco).replace(/\D/g, '');
      if (onlyDigits) valorVenda = parseInt(onlyDigits, 10);
    }

    status('Salvando no CRM…');
    const payload = {
      titulo,
      tipo: 'Apartamento',
      finalidade: 'venda',
      status: 'disponivel',
      bairro,
      cidade: bairro.includes('Barra') ? 'Rio de Janeiro' : 'Rio de Janeiro',
      estado: 'RJ',
      descricao: desc,
      quartos: parseNum(quartos),
      vagas: parseNum(vagas),
      valorVenda,
      preco,
      area,
      condicao,
      badge: badge || null,
      categoria,
      slug: slug || undefined,
      diferenciais,
      publicado: 1,
      destaque: badge === 'Destaque' ? 1 : 0,
      userId: sessionUser.id,
      capaUrl: fotosPaths[0] || null
    };

    const result = await apiJson('saveImovel', payload);
    if (!result.success) {
      status('❌ ' + (result.error || 'Erro ao salvar'), true);
      return;
    }

    const imovelId = result.id;
    status('Imóvel #' + imovelId + ' salvo. Enviando fotos…');

    // Upload de arquivos ainda no input (sem marca) se houver
    const fileInput = document.getElementById('fotoUploadInput');
    const files = Array.from(fileInput?.files || []);
    let uploaded = 0;
    for (let i = 0; i < files.length; i++) {
      const fd = new FormData();
      fd.append('imovelId', imovelId);
      fd.append('userId', sessionUser.id);
      fd.append('isCapa', i === 0 ? '1' : '0');
      fd.append('foto', files[i]);
      if (sessionToken) fd.append('token', sessionToken);
      const headers = {};
      if (sessionToken) headers['Authorization'] = 'Bearer ' + sessionToken;
      try {
        const r = await fetch(`${API_URL}?action=uploadImovelFoto`, {
          method: 'POST',
          headers,
          body: fd
        });
        const j = await r.json();
        if (j.success) uploaded++;
      } catch (e) {
        /* continua */
      }
    }

    status(
      `✅ Publicado no CRM e no site (id ${imovelId}` +
        (result.slug ? `, slug ${result.slug}` : '') +
        `)${uploaded ? ` · ${uploaded} foto(s)` : ''}.`
    );
  };

  // Integra login do overlay com API do CRM
  const originalFazerLogin = window.fazerLogin;
  window.fazerLogin = async function () {
    const user = document.getElementById('loginUser')?.value?.trim();
    const pass = document.getElementById('loginPass')?.value || '';
    const err = document.getElementById('loginErro');
    if (!user || !pass) {
      if (err) err.textContent = 'Informe usuário e senha';
      return;
    }
    try {
      const res = await fetch(`${API_URL}?action=login`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ username: user, password: pass })
      });
      const json = await res.json();
      if (!json.success) {
        if (err) err.textContent = json.error || 'Login inválido';
        // fallback login local se existir
        if (typeof originalFazerLogin === 'function') originalFazerLogin();
        return;
      }
      sessionUser = json.user;
      sessionToken = json.token || null;
      try {
        localStorage.setItem('vinutto_cadastro_session', JSON.stringify({ user: sessionUser, token: sessionToken }));
      } catch (e) {}
      document.getElementById('loginOverlay').style.display = 'none';
      document.getElementById('cadastroConteudo').style.display = 'block';
      if (err) err.textContent = '';
    } catch (e) {
      if (err) err.textContent = 'Erro de conexão com a API';
      if (typeof originalFazerLogin === 'function') originalFazerLogin();
    }
  };

  window.fazerLogout = function () {
    sessionUser = null;
    sessionToken = null;
    try {
      localStorage.removeItem('vinutto_cadastro_session');
    } catch (e) {}
    document.getElementById('loginOverlay').style.display = 'flex';
    document.getElementById('cadastroConteudo').style.display = 'none';
  };

  // Restaura sessão
  try {
    const raw = localStorage.getItem('vinutto_cadastro_session');
    if (raw) {
      const s = JSON.parse(raw);
      sessionUser = s.user;
      sessionToken = s.token;
      if (sessionUser) {
        document.getElementById('loginOverlay').style.display = 'none';
        document.getElementById('cadastroConteudo').style.display = 'block';
      }
    }
  } catch (e) {}

  // Botão extra na etapa 4
  document.addEventListener('DOMContentLoaded', function () {
    const wrap = document.querySelector('.bloco-codigo-titulo div') || document.querySelector('.bloco-codigo-titulo');
    if (!wrap || document.getElementById('btnSalvarCRM')) return;
    const btn = document.createElement('button');
    btn.id = 'btnSalvarCRM';
    btn.className = 'btn-copiar';
    btn.type = 'button';
    btn.innerHTML = '<span>Salvar no CRM + Site</span>';
    btn.onclick = function () {
      window.salvarImovelNoCRM();
    };
    wrap.appendChild(btn);
  });
})();