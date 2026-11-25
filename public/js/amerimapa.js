/* ==========================================================================
   AmeriMapa — JavaScript das telas (sem build). Integra com a API /api/v1.
   ========================================================================== */
(function () {
  'use strict';

  const meta = (name) => document.querySelector(`meta[name="${name}"]`)?.content || '';
  const $ = (sel, root = document) => root.querySelector(sel);
  const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));
  const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

  // ─── Cliente da API ──────────────────────────────────────────────────────
  class ApiError extends Error {
    constructor(status, code, message, details) { super(message); this.status = status; this.code = code; this.details = details; }
  }

  async function api(method, path, body) {
    const headers = { Accept: 'application/json' };
    const token = meta('api-token');
    if (token) headers.Authorization = `Bearer ${token}`;
    if (body !== undefined) headers['Content-Type'] = 'application/json';
    let res;
    try {
      res = await fetch(meta('api-base') + path, { method, headers, body: body === undefined ? undefined : JSON.stringify(body) });
    } catch (e) {
      throw new ApiError(0, 'NETWORK_ERROR', 'Sem conexão com o servidor. Verifique a internet.');
    }
    if (res.status === 204) return null;
    const json = await res.json().catch(() => ({}));
    if (!res.ok) {
      const err = json.error || {};
      throw new ApiError(res.status, err.code || 'HTTP_' + res.status, err.message || 'Erro inesperado.', err.details);
    }
    return json;
  }
  const get = (p) => api('GET', p).then((j) => j.data);
  const post = (p, b = {}) => api('POST', p, b).then((j) => j && j.data);

  // ─── Feedback: toast, voz e comemoração (RN17, RN18) ─────────────────────
  function toast(message, type = 'info', ms = 3800) {
    const box = $('#toasts');
    if (!box) return;
    const el = document.createElement('div');
    el.className = `toast ${type}`;
    el.setAttribute('role', type === 'error' ? 'alert' : 'status');
    el.textContent = message;
    box.appendChild(el);
    setTimeout(() => el.remove(), ms);
  }

  let audioOn = meta('a11y-audio') === '1';
  function speak(text, force = false) {
    if ((!audioOn && !force) || !('speechSynthesis' in window) || !text) return;
    window.speechSynthesis.cancel();
    const u = new SpeechSynthesisUtterance(text);
    u.lang = 'pt-BR';
    u.rate = 0.95;
    const voice = window.speechSynthesis.getVoices().find((v) => v.lang && v.lang.toLowerCase().startsWith('pt'));
    if (voice) u.voice = voice;
    window.speechSynthesis.speak(u);
  }

  function celebrate(achievements) {
    if (!achievements || !achievements.length) return;
    const a = achievements[0];
    const wrap = document.createElement('div');
    wrap.className = 'celebrate';
    wrap.innerHTML = `<div class="card"><div class="medal"><div class="disc m-${esc(a.color)}"><svg class="icon"><use href="#i-${esc(a.icon)}"/></svg></div></div>
      <h2>Nova conquista!</h2><p class="bold">${esc(a.name)}</p><button class="btn btn-primary" type="button">Oba!</button></div>`;
    document.body.appendChild(wrap);
    speak(`Parabéns! Você ganhou a conquista ${a.name}!`);
    const close = () => { wrap.remove(); celebrate(achievements.slice(1)); };
    wrap.querySelector('button').addEventListener('click', close);
    wrap.addEventListener('click', (e) => { if (e.target === wrap) close(); });
  }

  // ─── UI genérica: dropdowns, toggles, confirmações ───────────────────────
  document.addEventListener('click', (e) => {
    const trigger = e.target.closest('[data-dropdown] > button');
    $$('[data-dropdown] .dropdown.open').forEach((d) => { if (!trigger || d.parentElement !== trigger.parentElement) d.classList.remove('open'); });
    if (trigger) {
      const dd = trigger.parentElement.querySelector('.dropdown');
      dd.classList.toggle('open');
      trigger.setAttribute('aria-expanded', dd.classList.contains('open'));
    }
    const toggler = e.target.closest('[data-toggle]');
    if (toggler) $(toggler.dataset.toggle)?.classList.toggle('open');
    const shower = e.target.closest('[data-show]');
    if (shower) { e.preventDefault(); $(shower.dataset.show)?.classList.toggle('hidden'); }
  });
  document.addEventListener('submit', (e) => {
    const msg = e.target.dataset.confirm;
    if (msg && !window.confirm(msg)) e.preventDefault();
  });
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape') $$('.dropdown.open').forEach((d) => d.classList.remove('open')); });

  // Preferências de acessibilidade aplicadas na hora (tela Perfil)
  $$('[data-pref]').forEach((input) => {
    input.addEventListener('change', async () => {
      const cls = input.dataset.pref;
      if (cls) document.body.classList.toggle(cls, input.checked);
      if (input.name === 'audio_instructions') { audioOn = input.checked; if (audioOn) speak('Instruções por áudio ativadas.'); }
      const form = input.form;
      try {
        await fetch(form.action, { method: 'POST', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': meta('csrf-token') }, body: new FormData(form) });
        toast('Preferência salva!', 'success', 1800);
      } catch { toast('Não foi possível salvar.', 'error'); }
    });
  });
  $$('[data-speak]').forEach((b) => b.addEventListener('click', () => speak(b.dataset.speak, true)));

  // ─── Gráfico de linhas (SVG) ─────────────────────────────────────────────
  const PALETTE = { blue: '#2f6fec', green: '#1fa55b', orange: '#ff8a1f', pink: '#e85d9c', purple: '#7b5cf0', teal: '#14a3a3', yellow: '#f5b400' };
  function lineChart(el) {
    const data = JSON.parse(el.dataset.chart || '{}');
    const labels = data.labels || [];
    const series = data.series || [];
    const W = 560, H = 220, L = 34, R = 10, T = 10, B = 28;
    const x = (i) => L + (labels.length <= 1 ? 0 : (i * (W - L - R)) / (labels.length - 1));
    const y = (v) => T + (H - T - B) * (1 - v / 100);
    let svg = `<svg viewBox="0 0 ${W} ${H}" role="img" aria-label="${esc(data.title || 'Gráfico')}">`;
    [0, 25, 50, 75, 100].forEach((v) => { svg += `<line class="grid-line" x1="${L}" x2="${W - R}" y1="${y(v)}" y2="${y(v)}"/><text class="axis" x="${L - 8}" y="${y(v) + 4}" text-anchor="end">${v}</text>`; });
    const step = Math.ceil(labels.length / 8);
    labels.forEach((l, i) => { if (i % step && i !== labels.length - 1) return; svg += `<text class="axis" x="${x(i)}" y="${H - 6}" text-anchor="middle">${esc(l)}</text>`; });
    series.forEach((s) => {
      const color = PALETTE[s.color] || s.color || PALETTE.blue;
      const pts = s.values.map((v, i) => (v === null || v === undefined ? null : [x(i), y(v)])).filter(Boolean);
      if (!pts.length) return;
      const d = pts.map((p, i) => (i ? 'L' : 'M') + p[0].toFixed(1) + ',' + p[1].toFixed(1)).join('');
      if (series.length === 1 && pts.length > 1) svg += `<path class="area" fill="${color}" d="${d}L${pts[pts.length - 1][0]},${y(0)}L${pts[0][0]},${y(0)}Z"/>`;
      svg += `<path class="line" stroke="${color}" d="${d}"/>`;
      pts.forEach((p, i) => { svg += `<circle cx="${p[0]}" cy="${p[1]}" r="4" fill="${color}" stroke="#fff" stroke-width="2"><title>${esc(s.name || '')} ${Math.round(100 - ((p[1] - T) / (H - T - B)) * 100)}%</title></circle>`; });
    });
    svg += '</svg>';
    if (series.length > 1) svg += '<div class="legend">' + series.map((s) => `<span><i style="background:${PALETTE[s.color] || s.color}"></i>${esc(s.name)}</span>`).join('') + '</div>';
    if (!series.some((s) => s.values.some((v) => v !== null))) svg += '<p class="small muted center">Ainda não há partidas neste período.</p>';
    el.innerHTML = svg;
  }
  $$('[data-chart]').forEach(lineChart);

  // ─── Mapa SVG clicável ───────────────────────────────────────────────────
  class AmericasMap {
    constructor(root, { onSelect, focusIsos = [] } = {}) {
      this.root = root;
      this.svg = $('svg', root);
      this.onSelect = onSelect;
      this.full = this.svg.getAttribute('viewBox');
      this.tip = $('.map-tip', root);
      $$('.map-country, .map-hotspot', this.svg).forEach((el) => {
        if (el.classList.contains('map-country')) { el.setAttribute('tabindex', '0'); el.setAttribute('role', 'button'); el.setAttribute('aria-label', el.dataset.name); }
        el.addEventListener('click', () => this.select(el.dataset.iso));
        el.addEventListener('keydown', (e) => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); this.select(el.dataset.iso); } });
        el.addEventListener('mouseenter', () => { if (this.tip) { this.tip.textContent = el.dataset.name; this.tip.classList.remove('hidden'); } });
        el.addEventListener('mouseleave', () => this.tip?.classList.add('hidden'));
      });
      if (focusIsos.length) this.focus(focusIsos);
    }
    countries(iso) { return $$(`.map-country[data-iso="${iso}"]`, this.svg); }
    select(iso) {
      if (this.disabled) return;
      $$('.map-country.selected', this.svg).forEach((e) => e.classList.remove('selected'));
      this.countries(iso).forEach((e) => e.classList.add('selected'));
      this.onSelect && this.onSelect(iso);
    }
    mark(iso, cls) { if (iso) this.countries(iso).forEach((e) => e.classList.add(cls)); }
    clear(...classes) { $$('.map-country', this.svg).forEach((e) => e.classList.remove(...(classes.length ? classes : ['selected', 'correct', 'wrong']))); }
    focus(isos) {
      const set = new Set(isos);
      let box = null;
      $$('.map-country', this.svg).forEach((el) => {
        if (!set.has(el.dataset.iso)) { el.classList.add('dim'); return; }
        const b = el.getBBox();
        box = box ? { x1: Math.min(box.x1, b.x), y1: Math.min(box.y1, b.y), x2: Math.max(box.x2, b.x + b.width), y2: Math.max(box.y2, b.y + b.height) } : { x1: b.x, y1: b.y, x2: b.x + b.width, y2: b.y + b.height };
      });
      $$('.map-hotspot', this.svg).forEach((el) => el.classList.toggle('dim', !set.has(el.dataset.iso)));
      if (box) {
        const pad = 14;
        this.svg.setAttribute('viewBox', `${box.x1 - pad} ${box.y1 - pad} ${box.x2 - box.x1 + pad * 2} ${box.y2 - box.y1 + pad * 2}`);
      }
      this.focused = true;
    }
    showAll() {
      this.svg.setAttribute('viewBox', this.full);
      $$('.dim', this.svg).forEach((e) => e.classList.remove('dim'));
      this.focused = false;
    }
  }

  // ─── Mapa físico: status + eventos (RN20) ────────────────────────────────
  class DeviceLink {
    constructor({ box, preferred, matchId, onEvent }) {
      this.box = box; this.preferred = preferred; this.matchId = matchId; this.onEvent = onEvent;
      this.device = null; this.after = undefined; this.online = false; this.stopped = false;
    }
    render(state, title, text) {
      if (!this.box) return;
      this.box.className = 'device ' + state;
      this.box.innerHTML = `<span class="dot"></span><div class="grow"><b>${esc(title)}</b><small>${esc(text)}</small></div><svg class="icon"><use href="#i-${state === 'off' ? 'wifi-off' : 'wifi'}"/></svg>`;
    }
    async refresh() {
      try {
        const list = await get('/hardware/status');
        const pick = list.find((d) => d.deviceId === this.preferred && d.online) || list.find((d) => d.online) || list.find((d) => d.deviceId === this.preferred) || null;
        this.device = pick ? pick.deviceId : this.preferred;
        this.online = !!(pick && pick.online);
      } catch { this.online = false; }
      if (this.online) this.render('', 'Mapa conectado', this.matchId ? 'Pronto para receber seu toque' : 'Toque em um país do mapa tátil');
      else this.render('off', 'Mapa desconectado', 'Responda tocando no mapa da tela');
      return this.online;
    }
    async bind(matchId) {
      this.matchId = matchId;
      if (!this.online || !this.device) return false;
      try {
        await post('/hardware/bind-match', { deviceId: this.device, matchId });
        this.render('', 'Mapa conectado', 'Pronto para receber seu toque');
        return true;
      } catch (e) {
        this.render('off', 'Mapa desconectado', e.message);
        return false;
      }
    }
    async unbind() { if (this.device && this.online) { try { await post('/hardware/unbind', { deviceId: this.device }); } catch { /* ignora */ } } }
    async poll() {
      while (!this.stopped) {
        try {
          const q = new URLSearchParams();
          if (this.device) q.set('deviceId', this.device);
          if (this.matchId) q.set('matchId', this.matchId);
          if (this.after !== undefined) q.set('after', this.after);
          const res = await api('GET', '/hardware/events/poll?' + q.toString());
          this.after = res.meta.lastEventId;
          for (const ev of res.data) await this.handle(ev);
        } catch { /* rede instável: tenta de novo */ }
        await sleep(1000);
      }
    }
    async handle(ev) {
      if (ev.type === 'device.offline' && ev.deviceId === this.device) { this.online = false; this.render('off', 'Mapa desconectado', 'As respostas pelo mapa estão pausadas. Use o mapa da tela.'); toast('O mapa físico foi desconectado.', 'warn'); }
      if (ev.type === 'device.online' && ev.deviceId === this.device) { this.online = true; toast('Mapa físico conectado!', 'success'); if (this.matchId) await this.bind(this.matchId); else this.render('', 'Mapa conectado', 'Toque em um país do mapa tátil'); }
      if (ev.type === 'device.online' && !this.online && !this.device) { this.device = ev.deviceId; this.online = true; if (this.matchId) await this.bind(this.matchId); }
      if (ev.type === 'device.sensor_error' && ev.deviceId === this.device) toast('O mapa informou falha em um sensor.', 'warn');
      this.onEvent && this.onEvent(ev);
    }
    start() { this.poll(); }
    stop() { this.stopped = true; }
  }

  // ─── Regiões (iso <-> id) ────────────────────────────────────────────────
  let regionsCache = null;
  async function regions() {
    if (!regionsCache) {
      const list = await get('/regions');
      regionsCache = { list, byIso: {}, byId: {} };
      list.forEach((r) => { regionsCache.byId[r.id] = r; if (r.isoCode) regionsCache.byIso[r.isoCode] = r; });
    }
    return regionsCache;
  }

  // ─── Tela de jogo ────────────────────────────────────────────────────────
  async function initGame(root) {
    const cfg = JSON.parse(root.dataset.game);
    const ui = {
      prompt: $('[data-q-prompt]', root), counter: $('[data-q-counter]', root), steps: $('[data-q-steps]', root),
      chips: $('[data-q-chips]', root), timer: $('[data-q-timer]', root), answer: $('[data-answer]', root),
      confirm: $('[data-confirm]', root), repeat: $('[data-repeat]', root), skip: $('[data-skip]', root),
      next: $('[data-next]', root), finish: $('[data-finish]', root), score: $('[data-score]', root),
      select: $('[data-region-select]', root), mapRoot: $('[data-map]', root), showAll: $('[data-map-all]', root),
    };
    let match = null, question = null, selectedIso = null, busy = false, timerId = null, results = [], advanceId = null;
    const R = await regions();

    const map = new AmericasMap(ui.mapRoot, { focusIsos: cfg.focusIsos || [], onSelect: (iso) => choose(iso) });
    ui.showAll?.addEventListener('click', () => (map.focused ? map.showAll() : map.focus(cfg.focusIsos || [])));
    if (!(cfg.focusIsos || []).length) ui.showAll?.classList.add('hidden');

    // lista acessível (leitor de tela / teclado)
    if (ui.select) {
      const countries = R.list.filter((r) => r.type === 'COUNTRY' && (!(cfg.focusIsos || []).length || cfg.focusIsos.includes(r.isoCode))).sort((a, b) => a.name.localeCompare(b.name));
      const groups = R.list.filter((r) => r.type === 'REGION');
      ui.select.innerHTML = '<option value="">Escolher da lista…</option>' +
        `<optgroup label="Regiões">${groups.map((r) => `<option value="id:${r.id}">${esc(r.name)}</option>`).join('')}</optgroup>` +
        `<optgroup label="Países">${countries.map((r) => `<option value="${r.isoCode}">${esc(r.name)}</option>`).join('')}</optgroup>`;
      ui.select.addEventListener('change', () => { const v = ui.select.value; if (!v) return; if (v.startsWith('id:')) choose(null, +v.slice(3)); else map.select(v); });
    }

    function choose(iso, regionId) {
      if (!question || busy) return;
      const region = regionId ? R.byId[regionId] : R.byIso[iso];
      if (!region) return;
      selectedIso = iso || null;
      root.dataset.regionId = region.id;
      ui.answer.className = 'answer-box';
      ui.answer.innerHTML = `<div><div class="small muted bold">Sua resposta</div><div class="big">${esc(region.name)}</div></div>`;
      ui.confirm.disabled = false;
      ui.confirm.focus();
    }

    function renderSteps() {
      const total = match.mode.questionsPerMatch;
      ui.counter.textContent = total ? `Pergunta ${Math.min(match.answeredQuestions + (question ? 1 : 0), total)} de ${total}` : `Pergunta ${match.answeredQuestions + (question ? 1 : 0)}`;
      if (ui.steps && total) {
        ui.steps.innerHTML = Array.from({ length: total }, (_, i) => `<i class="${results[i] || (i === results.length && question ? 'now' : '')}"></i>`).join('');
      }
      ui.score.textContent = match.totalScore;
    }

    function startTimer() {
      clearInterval(timerId);
      if (!question || !question.expiresAt) { ui.timer.classList.add('hidden'); return; }
      ui.timer.classList.remove('hidden');
      const end = new Date(question.expiresAt).getTime();
      const tick = () => {
        const left = Math.max(0, Math.ceil((end - Date.now()) / 1000));
        ui.timer.innerHTML = `<svg class="icon"><use href="#i-clock"/></svg> ${left}s`;
        ui.timer.classList.toggle('low', left <= 5);
        if (left <= 0) { clearInterval(timerId); timeUp(); }
      };
      tick();
      timerId = setInterval(tick, 250);
    }

    function showQuestion(q) {
      question = q;
      selectedIso = null;
      delete root.dataset.regionId;
      map.clear();
      map.disabled = false;
      ui.prompt.textContent = q.prompt;
      const diff = { EASY: ['Fácil', 'green'], MEDIUM: ['Média', 'yellow'], HARD: ['Difícil', 'red'] }[q.difficulty] || ['', 'gray'];
      ui.chips.innerHTML = `<span class="chip ${diff[1]}">${diff[0]}</span>` + (q.attemptsLeft !== null ? ` <span class="chip gray">${q.attemptsLeft} tentativa(s)</span>` : ' <span class="chip gray">Tentativas livres</span>');
      ui.answer.className = 'answer-box';
      ui.answer.innerHTML = '<div><svg class="icon" style="width:40px;height:40px;color:#2f6fec"><use href="#i-compass"/></svg><div class="bold muted">Sua resposta aparecerá aqui</div><div class="small muted">Toque no mapa tátil ou no mapa da tela</div></div>';
      ui.confirm.disabled = true;
      ui.confirm.classList.remove('hidden');
      ui.next.classList.add('hidden');
      ui.skip.classList.remove('hidden');
      if (ui.select) ui.select.value = '';
      renderSteps();
      startTimer();
      speak(q.prompt);
    }

    async function nextQuestion() {
      clearTimeout(advanceId);
      if (busy) return;
      busy = true;
      try {
        const res = await post(`/matches/${match.id}/next-question`);
        match = res.match;
        if (res.finished) return goResult();
        showQuestion(res.question);
      } catch (e) {
        if (e.code === 'QUESTION_STILL_ACTIVE') { const m = await get(`/matches/${match.id}`); match = m; if (m.activeQuestion) showQuestion(m.activeQuestion); }
        else if (e.code === 'MATCH_NOT_IN_PROGRESS') goResult();
        else toast(e.message, 'error');
      } finally { busy = false; }
    }

    function showOutcome(o, fromMap) {
      clearInterval(timerId);
      match = Object.assign(match, o.match);
      const kind = o.correct ? 'ok' : o.questionClosed ? 'bad' : 'warn';
      const icon = o.correct ? 'check-circle' : o.questionClosed ? 'x' : 'refresh';
      const pts = o.points && o.points.total ? `<div class="points-pop">+${o.points.total} pontos${o.points.speedBonus ? ' ⚡' : ''}${o.points.streakBonus ? ' 🔥' : ''}</div>` : '';
      const touched = o.touchedRegion ? `<div class="small muted">Você ${fromMap ? 'tocou em' : 'escolheu'}: <b>${esc(o.touchedRegion.name)}</b></div>` : '';
      ui.answer.className = 'answer-box ' + kind;
      ui.answer.innerHTML = `<div><svg class="icon" style="width:40px;height:40px"><use href="#i-${icon}"/></svg><div class="big">${esc(o.feedback.message)}</div>${touched}${pts}</div>`;
      speak(o.feedback.message);
      map.clear('selected');
      if (o.touchedRegion) map.mark(R.byId[o.touchedRegion.id]?.isoCode, o.correct ? 'correct' : 'wrong');
      if (o.correctRegion) map.mark(o.correctRegion.isoCode || R.byId[o.correctRegion.id]?.isoCode, 'correct');
      celebrate(o.newAchievements);
      if (!o.questionClosed) {
        question.attemptsLeft = o.attemptsLeft;
        ui.confirm.disabled = true;
        ui.chips.querySelector('.chip.gray') && (ui.chips.querySelector('.chip.gray').textContent = o.attemptsLeft === null ? 'Tentativas livres' : `${o.attemptsLeft} tentativa(s)`);
        renderSteps();
        startTimer();
        return;
      }
      results.push(o.correct ? 'ok' : 'bad');
      question = null;
      map.disabled = true;
      renderSteps();
      ui.confirm.classList.add('hidden');
      ui.skip.classList.add('hidden');
      ui.next.classList.remove('hidden');
      const done = o.match.status !== 'IN_PROGRESS';
      ui.next.innerHTML = done ? 'Ver resultado <svg class="icon"><use href="#i-arrow-right"/></svg>' : 'Próxima pergunta <svg class="icon"><use href="#i-arrow-right"/></svg>';
      ui.next.focus();
      if (fromMap || done) advanceId = setTimeout(() => (done ? goResult() : nextQuestion()), 3500);
    }

    async function confirmAnswer() {
      const regionId = +root.dataset.regionId;
      if (!question || !regionId || busy) return;
      busy = true;
      ui.confirm.disabled = true;
      try { showOutcome(await post(`/matches/${match.id}/answers`, { regionId, source: 'SCREEN' }), false); }
      catch (e) { handleAnswerError(e); }
      finally { busy = false; }
    }

    async function skip(isTimeout) {
      if (!question || busy) return;
      busy = true;
      try { showOutcome(await post(`/matches/${match.id}/skip`), false); }
      catch (e) { handleAnswerError(e, isTimeout); }
      finally { busy = false; }
    }
    const timeUp = () => skip(true);

    async function handleAnswerError(e) {
      if (e.code === 'NO_ACTIVE_QUESTION' || e.code === 'ANSWER_ALREADY_PROCESSED') {
        const m = await get(`/matches/${match.id}`);
        match = m;
        if (m.status !== 'IN_PROGRESS') return goResult();
        if (m.activeQuestion) showQuestion(m.activeQuestion); else nextQuestion();
        return;
      }
      if (e.code === 'MATCH_NOT_IN_PROGRESS') return goResult();
      toast(e.message, 'error');
      ui.confirm.disabled = false;
    }

    function goResult() {
      link.stop();
      clearInterval(timerId);
      window.location.href = cfg.resultUrl.replace('__ID__', match.id);
    }

    ui.confirm.addEventListener('click', confirmAnswer);
    ui.skip.addEventListener('click', () => skip(false));
    ui.next.addEventListener('click', () => (match.status !== 'IN_PROGRESS' ? goResult() : nextQuestion()));
    ui.repeat.addEventListener('click', () => question && speak(question.prompt, true));
    ui.finish.addEventListener('click', async () => {
      const scored = cfg.scored;
      if (!window.confirm(scored ? 'Encerrar a partida agora? As perguntas restantes não serão contadas.' : 'Concluir o treino?')) return;
      try { match = await post(`/matches/${match.id}/finish`, { status: scored ? 'ABANDONED' : 'COMPLETED' }); } catch (e) { /* já encerrada */ }
      await link.unbind();
      goResult();
    });

    // Eventos do mapa físico: resposta avaliada vinda do ESP32
    const link = new DeviceLink({
      box: $('[data-device]', root), preferred: cfg.defaultDevice, matchId: null,
      onEvent: (ev) => {
        if (!match || ev.matchId !== match.id) {
          if (ev.type === 'touch.unbound' && question) toast(`Toque em ${ev.payload.region.name}, mas o mapa não está vinculado a esta partida.`, 'warn');
          return;
        }
        if (ev.type === 'answer.evaluated' && question) showOutcome(ev.payload.outcome, true);
        if (ev.type === 'touch.rejected') toast(ev.payload.message || 'Toque não reconhecido.', 'warn');
        if (ev.type === 'match.finished') setTimeout(goResult, 3500);
      },
    });

    // Cria ou retoma a partida
    try {
      if (cfg.matchId) match = await get(`/matches/${cfg.matchId}`);
      else {
        match = await post('/matches', cfg.start);
        history.replaceState(null, '', cfg.playUrl + '?partida=' + match.id);
      }
    } catch (e) {
      root.innerHTML = `<div class="card center"><h2>Não foi possível começar</h2><p>${esc(e.message)}</p><a class="btn btn-primary" href="${cfg.backUrl}">Voltar às atividades</a></div>`;
      speak(e.message);
      return;
    }
    if (match.status !== 'IN_PROGRESS') return goResult();
    const answers = await get(`/matches/${match.id}/answers`).catch(() => []);
    const order = [];
    const last = new Map();
    answers.forEach((a) => { if (!last.has(a.questionId)) order.push(a.questionId); last.set(a.questionId, a); });
    results = order.slice(0, match.answeredQuestions).map((id) => (last.get(id).result === 'CORRECT' ? 'ok' : 'bad'));

    link.matchId = match.id;
    if (await link.refresh()) await link.bind(match.id);
    link.start();
    window.addEventListener('beforeunload', () => { link.stop(); });

    if (match.activeQuestion) showQuestion(match.activeQuestion); else await nextQuestion();
  }

  // ─── Modo Exploração ─────────────────────────────────────────────────────
  async function initExplore(root) {
    const cfg = JSON.parse(root.dataset.explore);
    const R = await regions();
    const card = $('[data-info]', root);
    const counter = $('[data-explored-count]', root);
    const explored = new Set(cfg.explored || []);
    const map = new AmericasMap($('[data-map]', root), { onSelect: (iso) => show(R.byIso[iso], 'SCREEN') });
    explored.forEach((iso) => map.mark(iso, 'explored'));

    async function show(region, source) {
      if (!region) return;
      map.clear();
      map.mark(region.isoCode, 'correct');
      const parent = region.parentId ? R.byId[region.parentId] : null;
      card.innerHTML = `<div class="row" style="align-items:flex-start"><div class="act"><div class="ico"><svg class="icon"><use href="#i-pin"/></svg></div></div><div class="grow">
        <span class="chip ${source === 'MAP' ? 'green' : ''}">${source === 'MAP' ? 'Toque no mapa tátil' : 'Mapa da tela'}</span>
        <h2 style="margin-top:8px">${esc(region.name)}</h2>
        ${parent ? `<p class="muted bold">${esc(parent.name)}</p>` : ''}
        ${region.capital ? `<p><b>Capital:</b> ${esc(region.capital)}</p>` : ''}
        ${region.language ? `<p><b>Idioma:</b> ${esc(region.language)}</p>` : ''}
        ${region.info ? `<p class="card soft-yellow tight">💡 ${esc(region.info)}</p>` : (region.shortDescription && !region.capital ? `<p>${esc(region.shortDescription)}</p>` : '')}
        <button class="btn btn-outline btn-sm" type="button" data-say><svg class="icon"><use href="#i-volume"/></svg> Ouvir</button></div></div>`;
      const sentence = `${region.name}.` + (region.capital ? ` A capital é ${region.capital}.` : '') + (region.language ? ` Idioma: ${region.language}.` : '') + (region.info ? ' ' + region.info : '');
      $('[data-say]', card).addEventListener('click', () => speak(sentence, true));
      speak(sentence);
      try {
        const res = await post(`/regions/${region.id}/explore`);
        if (region.isoCode && !explored.has(region.isoCode)) { explored.add(region.isoCode); if (counter) counter.textContent = explored.size; }
        celebrate(res.newAchievements);
      } catch { /* visitante ou offline: só mostra */ }
    }

    const link = new DeviceLink({
      box: $('[data-device]', root), preferred: cfg.defaultDevice,
      onEvent: (ev) => {
        if ((ev.type === 'touch.unbound' || ev.type === 'region.explored') && ev.payload?.region) show(R.byId[ev.payload.region.id] || ev.payload.region, 'MAP');
      },
    });
    await link.refresh();
    link.start();
  }

  // ─── Painel do dispositivo (professor): atualização automática ──────────
  function initDevicePanel(root) {
    const refresh = async () => {
      try {
        const list = await get('/hardware/status');
        list.forEach((d) => {
          const row = $(`[data-device-row="${CSS.escape(d.deviceId)}"]`, root);
          if (!row) return;
          $('[data-online]', row).innerHTML = d.online ? '<span class="chip green">Conectado</span>' : '<span class="chip red">Desconectado</span>';
          $('[data-seen]', row).textContent = d.secondsSinceLastSeen === null ? '—' : `há ${d.secondsSinceLastSeen < 60 ? d.secondsSinceLastSeen + ' s' : Math.round(d.secondsSinceLastSeen / 60) + ' min'}`;
          $('[data-match]', row).textContent = d.boundMatchId ? `#${d.boundMatchId}` : '—';
        });
      } catch { /* ignora */ }
    };
    setInterval(refresh, 4000);
  }

  // Seletor de perguntas (formulário de atividade)
  function initQuestionPicker(root) {
    const search = $('[data-q-search]', root);
    const phase = $('[data-q-phase]', root);
    const counter = $('[data-q-count]', root);
    const items = $$('label[data-text]', root);
    const update = () => {
      const t = (search.value || '').toLowerCase();
      const p = phase.value;
      items.forEach((l) => l.classList.toggle('hidden', !(l.dataset.text.includes(t) && (!p || l.dataset.phase === p))));
      counter.textContent = $$('input:checked', root).length;
    };
    search.addEventListener('input', update);
    phase.addEventListener('change', update);
    root.addEventListener('change', update);
    $('[data-q-all]', root)?.addEventListener('click', () => { items.filter((l) => !l.classList.contains('hidden')).forEach((l) => { $('input', l).checked = true; }); update(); });
    $('[data-q-none]', root)?.addEventListener('click', () => { items.filter((l) => !l.classList.contains('hidden')).forEach((l) => { $('input', l).checked = false; }); update(); });
    update();
  }

  document.addEventListener('DOMContentLoaded', () => {
    $$('[data-game]').forEach((el) => initGame(el).catch((e) => toast(e.message || 'Erro ao carregar o jogo.', 'error')));
    $$('[data-explore]').forEach((el) => initExplore(el).catch((e) => toast(e.message || 'Erro ao carregar o mapa.', 'error')));
    $$('[data-device-panel]').forEach(initDevicePanel);
    $$('[data-question-picker]').forEach(initQuestionPicker);
    $$('[data-speak-on-load]').forEach((el) => speak(el.dataset.speakOnLoad));
  });

  window.AmeriMapa = { api, get, post, toast, speak, regions, AmericasMap, DeviceLink };
})();
