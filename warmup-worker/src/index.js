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

// I cron UTC non seguono l'ora legale: si filtra sull'ora di Roma.
const ROME_WARMUP_TIMES = ["06:50", "07:10", "07:40"];

function isRomeWarmupTime(time) {
  const romeTime = new Intl.DateTimeFormat("en-GB", {
    timeZone: "Europe/Rome",
    hour: "2-digit",
    minute: "2-digit",
  }).format(new Date(time));
  return ROME_WARMUP_TIMES.includes(romeTime);
}

export default {
  async scheduled(controller, env, ctx) {
    if (!isRomeWarmupTime(controller.scheduledTime)) {
      return;
    }
    ctx.waitUntil(wakeRender());
  },
};
