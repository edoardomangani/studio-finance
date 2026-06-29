// Sveglia il servizio Render tenendo aperta la richiesta finché il boot
// (~45s a freddo) arriva in fondo. cron-job.org non ci riesce: molla a 30s.
// Qui ctx.waitUntil() lascia proseguire il fetch oltre i 10ms di CPU del free.
const TARGET = "https://studio-finance.onrender.com/up";

async function wakeRender() {
  for (let i = 0; i < 6; i++) {
    try {
      const res = await fetch(TARGET, { signal: AbortSignal.timeout(60000) });
      if (res.ok) {
        return;
      }
    } catch (_) {
      // 503/timeout durante lo spin-up: si ritenta.
    }
    await new Promise((r) => setTimeout(r, 5000));
  }
}

export default {
  async scheduled(controller, env, ctx) {
    ctx.waitUntil(wakeRender());
  },
};
