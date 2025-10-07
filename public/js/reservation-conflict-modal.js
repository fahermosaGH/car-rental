(function () {
  const $ = (s) => document.querySelector(s);

  const vehicleEl = $('#Reservation_vehicle') || document.querySelector('[name$="[vehicle]"]');
  const startEl   = $('#Reservation_startAt') || document.querySelector('[name$="[startAt]"]');
  const endEl     = $('#Reservation_endAt')   || document.querySelector('[name$="[endAt]"]');
  const submitBtn = document.querySelector('form button[type="submit"]');
  if (!vehicleEl || !startEl || !endEl || !submitBtn) return;

  // Crear link "Ver conflicto" si no existe
  let link = document.querySelector('#view-conflict-link');
  if (!link) {
    link = document.createElement('button');
    link.type = 'button';
    link.id = 'view-conflict-link';
    link.className = 'btn btn-outline-danger';
    link.style.marginLeft = '8px';
    link.textContent = 'Ver conflicto';
    link.disabled = true; // habilitamos sólo cuando el check diga NO disponible
    submitBtn.insertAdjacentElement('afterend', link);
  }

  // Crear modal mínimo (Bootstrap-like sin dependencia)
  let modal = document.querySelector('#conflict-modal');
  if (!modal) {
    modal = document.createElement('div');
    modal.id = 'conflict-modal';
    modal.style.position = 'fixed';
    modal.style.left = 0; modal.style.top = 0; modal.style.right = 0; modal.style.bottom = 0;
    modal.style.background = 'rgba(0,0,0,0.5)';
    modal.style.display = 'none';
    modal.style.zIndex = 9999;
    modal.innerHTML = `
      <div style="max-width:560px;margin:10% auto;background:#fff;border-radius:10px;overflow:hidden">
        <div style="padding:12px 16px;border-bottom:1px solid #eee;font-weight:600">Reservas en conflicto</div>
        <div id="conflict-body" style="padding:16px;font-size:14px">Cargando…</div>
        <div style="padding:12px 16px;border-top:1px solid #eee;text-align:right">
          <button id="conflict-close" class="btn btn-secondary">Cerrar</button>
        </div>
      </div>
    `;
    document.body.appendChild(modal);
    modal.querySelector('#conflict-close').addEventListener('click', () => modal.style.display = 'none');
    modal.addEventListener('click', (e) => { if (e.target === modal) modal.style.display = 'none'; });
  }
  const conflictBody = modal.querySelector('#conflict-body');

  async function fetchConflicts() {
    const v = vehicleEl.value, s = startEl.value, e = endEl.value;
    if (!v || !s || !e) return [];
    try {
      const res = await fetch(`/api/conflicts?vehicle=${encodeURIComponent(v)}&start=${encodeURIComponent(s)}&end=${encodeURIComponent(e)}`, { cache: 'no-store' });
      if (!res.ok) return [];
      const data = await res.json();
      return data.conflicts || [];
    } catch { return []; }
  }

  async function openModal() {
    conflictBody.textContent = 'Cargando…';
    modal.style.display = 'block';
    const items = await fetchConflicts();
    if (!items.length) {
      conflictBody.textContent = 'No se encontraron conflictos (o no se pudo cargar).';
      return;
    }
    conflictBody.innerHTML = items.map(it => `
      <div style="padding:8px 0;border-bottom:1px dashed #e5e5e5">
        <div><b>#${it.id}</b> — ${it.customer ? it.customer : 'Cliente N/D'}</div>
        <div>${it.startAt} → ${it.endAt}</div>
        ${it.adminUrl ? `<div><a href="${it.adminUrl}">Abrir en admin</a></div>` : ''}
      </div>
    `).join('');
  }

  // Habilitar/deshabilitar el botón según el hint que pone reservation-check.js
  const hint = document.querySelector('#availability-hint');
  const observer = new MutationObserver(() => {
    // Si está mostrando 🚫, habilitamos el botón "Ver conflicto"
    const txt = (hint?.textContent || '').toLowerCase();
    link.disabled = !txt.includes('no disponible') && !txt.includes('conflicto') && !txt.includes('solapa');
  });
  if (hint) observer.observe(hint, { childList: true, subtree: true });

  link.addEventListener('click', openModal);
})();
