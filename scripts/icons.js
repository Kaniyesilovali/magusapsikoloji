// Çizgi ikon seti — emoji yerine.
//
// Sitenin zaten kullandığı dil: 24×24 viewBox, dolgu yok, currentColor kontur,
// kalınlık 2, yuvarlak uç ve birleşim (güven satırı, footer iletişim ikonları,
// nav okları hep böyle). Emoji bu dilin dışında duran tek şeydi.
//
// Gövdeler yalnız <path>/<circle> içerir; sarmalayıcı <svg> shortcode'da
// üretilir, böylece boyut ve renk çağrı yerinden verilir.

const bodies = {
  // ── Gizlilik ve çalışma ilkeleri ──
  lock: '<rect x="5" y="10.5" width="14" height="9.5" rx="2"/><path d="M8 10.5V7.5a4 4 0 0 1 8 0v3"/>',
  globe: '<circle cx="12" cy="12" r="9"/><path d="M3 12h18"/><path d="M12 3c2.4 2.6 2.4 15.4 0 18"/><path d="M12 3c-2.4 2.6-2.4 15.4 0 18"/>',
  clipboard: '<rect x="5" y="4.5" width="14" height="16" rx="2"/><path d="M9.5 4.5V3.8a1.3 1.3 0 0 1 1.3-1.3h2.4a1.3 1.3 0 0 1 1.3 1.3v.7Z"/><path d="M9 11h6"/><path d="M9 15h4"/>',
  people: '<circle cx="9" cy="8" r="3"/><path d="M3.5 19.5a5.5 5.5 0 0 1 11 0"/><path d="M16 5.6a3 3 0 0 1 0 4.8"/><path d="M17.2 14.4a5.5 5.5 0 0 1 3.3 5.1"/>',
  heart: '<path d="M12 20.2s-7.2-4.4-7.2-9.4A4.3 4.3 0 0 1 12 7.8a4.3 4.3 0 0 1 7.2 3c0 5-7.2 9.4-7.2 9.4Z"/>',
  sprout: '<path d="M12 21v-7.5"/><path d="M12 13.5C8.4 13.5 5.5 10.9 5.5 7.3c3.6 0 6.5 2.6 6.5 6.2Z"/><path d="M12 13.5c0-3.6 2.9-6.2 6.5-6.2 0 3.6-2.9 6.2-6.5 6.2Z"/>',

  // ── Çalışma alanları ──
  child: '<circle cx="12" cy="7" r="3.5"/><path d="M6 20.5a6 6 0 0 1 12 0"/>',
  // Hero madalyonundaki beyin ile aynı çizim — sitenin kendi ikonu
  brain: '<path d="M9.5 2A2.5 2.5 0 0 1 12 4.5v15a2.5 2.5 0 0 1-4.96-.46 2.5 2.5 0 0 1-1.03-4.47A3 3 0 0 1 4.5 8.5 3 3 0 0 1 7 6a2.5 2.5 0 0 1 2.5-4Z"/><path d="M14.5 2A2.5 2.5 0 0 0 12 4.5v15a2.5 2.5 0 0 0 4.96-.46 2.5 2.5 0 0 0 1.03-4.47A3 3 0 0 0 19.5 8.5 3 3 0 0 0 17 6a2.5 2.5 0 0 0-2.5-4Z"/>',
  spark: '<path d="M12 3.2l1.7 5.1 5.1 1.7-5.1 1.7L12 16.8l-1.7-5.1L5.2 10l5.1-1.7L12 3.2Z"/><path d="M18.5 15.5l.7 2 2 .7-2 .7-.7 2-.7-2-2-.7 2-.7Z"/>',
  family: '<circle cx="7.5" cy="7" r="2.8"/><circle cx="16.5" cy="7" r="2.8"/><path d="M2.8 16.5a4.7 4.7 0 0 1 9.4 0"/><path d="M11.8 16.5a4.7 4.7 0 0 1 9.4 0"/><circle cx="12" cy="16.8" r="2"/><path d="M8.8 22a3.3 3.3 0 0 1 6.4 0"/>',
  rain: '<path d="M7 14.5a4 4 0 0 1 .5-8 5.2 5.2 0 0 1 9.8 1.2A3.6 3.6 0 0 1 17 14.5H7Z"/><path d="M8.5 18v2"/><path d="M12 18.5v2.5"/><path d="M15.5 18v2"/>',
  puzzle: '<path d="M4.5 9.5h1.8a1.9 1.9 0 1 0 1.9-2.6V4.8h5.6v2.1a1.9 1.9 0 1 0 1.9 2.6h1.8v9.7H4.5V9.5Z"/>',
  burst: '<path d="M13 3l-2.6 6.2 3.4 1.1-4 4.2 2.2 1.2L9.6 21"/><path d="M4.5 12h3"/><path d="M16.5 12h3"/>',
  check: '<path d="M5 12.5l4.5 4.5L19 7.5"/>',
  search: '<circle cx="10.5" cy="10.5" r="6.5"/><path d="M15.2 15.2L21 21"/>',

  // ── Kişiler ve süreç ──
  person: '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="10" r="2.8"/><path d="M6.8 19.2a5.6 5.6 0 0 1 10.4 0"/>',
  wave: '<path d="M3 9c2.2 0 2.2 2 4.5 2S9.7 9 12 9s2.2 2 4.5 2S18.7 9 21 9"/><path d="M3 15c2.2 0 2.2 2 4.5 2s2.2-2 4.5-2 2.2 2 4.5 2 2.2-2 4.5-2"/>',
  tap: '<path d="M6.5 3.5l11.5 6.8-5 1.4-2.2 5.1L6.5 3.5Z"/><path d="M13.5 15.5L19 21"/>',
  shield: '<path d="M12 3l7 2.8v5.4c0 4.3-2.9 7.7-7 9.8-4.1-2.1-7-5.5-7-9.8V5.8L12 3Z"/><path d="M9.2 12.2l2 2 3.6-3.8"/>',
  key: '<circle cx="8" cy="15.5" r="4"/><path d="M10.9 12.6L20 3.5"/><path d="M16.8 6.7l2.4 2.4"/><path d="M14.4 9.1l2 2"/>',
  pin: '<path d="M12 21s6.5-6.1 6.5-10.5A6.5 6.5 0 0 0 5.5 10.5C5.5 14.9 12 21 12 21Z"/><circle cx="12" cy="10.3" r="2.4"/>',
  calendar: '<rect x="4" y="5.5" width="16" height="15" rx="2"/><path d="M4 10h16"/><path d="M8.5 3v4"/><path d="M15.5 3v4"/>',
  card: '<rect x="3" y="5.5" width="18" height="13" rx="2.5"/><path d="M3 10h18"/><path d="M6.5 14.5h3.5"/>',

  // ── Beyin–beden diyagramı ──
  thought: '<path d="M8 13.5a4 4 0 0 1 .4-7.4A4.8 4.8 0 0 1 17.4 7 3.4 3.4 0 0 1 17 13.5H8Z"/><circle cx="7" cy="17.5" r="1.6"/><circle cx="4" cy="20.5" r="1"/>',
  lungs: '<path d="M12 3.5v8"/><path d="M9.6 6.2C6.8 7.2 5 10 5 13.2v4.3a1.8 1.8 0 0 0 2.5 1.7l2.3-1a1.8 1.8 0 0 0 1.1-1.7V11.5"/><path d="M14.4 6.2c2.8 1 4.6 3.8 4.6 7v4.3a1.8 1.8 0 0 1-2.5 1.7l-2.3-1a1.8 1.8 0 0 1-1.1-1.7V11.5"/>',
  run: '<circle cx="14.5" cy="4.8" r="2"/><path d="M13.5 20l1.3-5-3-2.6.8-4.4"/><path d="M12.6 8l-3.4 1.6-1.4 3"/><path d="M12.8 12.4l3.6 1.6 1.4 3.4"/><path d="M13.5 20l-3.8-1.6"/>',
};

const wrap = (name, cls = 'w-5 h-5', strokeWidth = 2) => {
  const body = bodies[name];
  if (!body) throw new Error(`icons.js: "${name}" diye bir ikon yok`);
  return `<svg class="${cls}" viewBox="0 0 24 24" fill="none" stroke="currentColor" `
    + `stroke-width="${strokeWidth}" stroke-linecap="round" stroke-linejoin="round" `
    + `aria-hidden="true">${body}</svg>`;
};

module.exports = { bodies, wrap, names: Object.keys(bodies) };
