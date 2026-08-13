/**
 * Carrega o catálogo do CRM (API) e mantém compatibilidade com imoveis-data.js
 * Uso no site público:
 *   <script src="imoveis-loader.js"></script>
 *   <script src="imoveis-data.js"></script>  <!-- fallback estático -->
 *   <script src="assets/js/home.js"></script>
 *
 * Defina a URL da API (mesmo host ou CRM):
 *   window.VINUTTO_API_URL = 'https://SEU_DOMINIO/api.php';
 */
(function (global) {
  const API_URL =
    global.VINUTTO_API_URL ||
    global.API_URL ||
    (location.hostname.includes('vinutto')
      ? 'https://vinutto.com/api.php'
      : 'api.php');

  function buildDB(catalogo) {
    const db = {};
    const prontos = (catalogo && catalogo.prontos) || [];
    const lancamentos = (catalogo && catalogo.lancamentos) || [];
    [...prontos, ...lancamentos].forEach((im) => {
      im.link = im.link || `https://vinutto.com/imovel.html?id=${im.id}`;
      if (!im.badgeClass) {
        im.badgeClass =
          im.badge === 'Lançamento' || im.badge === 'Novo'
            ? 'badge-red'
            : 'badge-gold';
      }
      db[im.id] = im;
    });
    return db;
  }

  async function fetchCatalogo() {
    const url = `${API_URL}?action=listImoveisPublico`;
    const res = await fetch(url, { method: 'GET', credentials: 'omit' });
    if (!res.ok) throw new Error('HTTP ' + res.status);
    const json = await res.json();
    if (!json.success || !json.IMOVEIS) throw new Error(json.error || 'Catálogo inválido');
    return json.IMOVEIS;
  }

  async function fetchImovel(id) {
    const url = `${API_URL}?action=getImovelPublico&id=${encodeURIComponent(id)}`;
    const res = await fetch(url, { method: 'GET', credentials: 'omit' });
    if (!res.ok) throw new Error('HTTP ' + res.status);
    const json = await res.json();
    if (!json.success || !json.imovel) throw new Error(json.error || 'Imóvel não encontrado');
    return json.imovel;
  }

  /**
   * Resolve IMOVEIS / IMOVEIS_DB: prioriza API, cai no estático se falhar.
   */
  global.loadVinuttoImoveis = async function loadVinuttoImoveis() {
    try {
      const catalogo = await fetchCatalogo();
      if (
        (catalogo.prontos && catalogo.prontos.length) ||
        (catalogo.lancamentos && catalogo.lancamentos.length)
      ) {
        global.IMOVEIS = catalogo;
        global.IMOVEIS_DB = buildDB(catalogo);
        global.IMOVEIS_SOURCE = 'api';
        return global.IMOVEIS;
      }
    } catch (e) {
      console.warn('[vinutto] API indisponível, usando imoveis-data.js', e);
    }
    if (global.IMOVEIS) {
      global.IMOVEIS_DB = buildDB(global.IMOVEIS);
      global.IMOVEIS_SOURCE = 'static';
      return global.IMOVEIS;
    }
    global.IMOVEIS = { prontos: [], lancamentos: [] };
    global.IMOVEIS_DB = {};
    global.IMOVEIS_SOURCE = 'empty';
    return global.IMOVEIS;
  };

  global.fetchVinuttoImovel = fetchImovel;
  global.VINUTTO_API_URL = API_URL;
})(typeof window !== 'undefined' ? window : globalThis);