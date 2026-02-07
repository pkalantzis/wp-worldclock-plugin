/* WP Multi Timezone Clocks (v0.1.0) */
(function () {
  function safeParseJSON(str) {
    try { return JSON.parse(str); } catch (e) { return null; }
  }

  function isLocalTZ(tz) {
    return !tz || tz === "local";
  }

  function resolvedLocalTZName() {
    try {
      return Intl.DateTimeFormat().resolvedOptions().timeZone || "Local time";
    } catch (e) {
      return "Local time";
    }
  }

  /** ---------- Digital ---------- */

  function makeDigitalFormatter(tz, cfg) {
    const opts = {
      hour: "2-digit",
      minute: "2-digit",
      second: cfg.seconds ? "2-digit" : undefined,
      hour12: cfg.format12,
      year: cfg.showDate ? "numeric" : undefined,
      month: cfg.showDate ? "short" : undefined,
      day: cfg.showDate ? "2-digit" : undefined,
    };
    if (!isLocalTZ(tz)) opts.timeZone = tz;
    return new Intl.DateTimeFormat(undefined, opts);
  }

  function formatDigitalParts(dtf, now) {
    const parts = dtf.formatToParts(now);
    const map = {};
    for (const p of parts) map[p.type] = p.value;

    const hour = map.hour ?? "--";
    const minute = map.minute ?? "--";
    const second = map.second ?? null;
    const dayPeriod = map.dayPeriod ?? "";

    let time = `${hour}:${minute}`;
    if (second) time += `:${second}`;
    if (dayPeriod) time += ` ${dayPeriod}`;

    let date = "";
    if (map.year && map.month && map.day) date = `${map.day} ${map.month} ${map.year}`;

    return { time, date };
  }

  /** ---------- Analog ---------- */

  function getTimePartsInTZ(tz, dateObj) {
    if (isLocalTZ(tz)) {
      return { hh: dateObj.getHours(), mm: dateObj.getMinutes(), ss: dateObj.getSeconds() };
    }

    const dtf = new Intl.DateTimeFormat("en-GB", {
      timeZone: tz,
      hour: "2-digit",
      minute: "2-digit",
      second: "2-digit",
      hour12: false,
    });

    const parts = dtf.formatToParts(dateObj);
    const map = {};
    for (const p of parts) map[p.type] = p.value;

    const hh = parseInt(map.hour, 10);
    const mm = parseInt(map.minute, 10);
    const ss = parseInt(map.second, 10);

    if (Number.isNaN(hh) || Number.isNaN(mm) || Number.isNaN(ss)) {
      throw new Error("Invalid time parts");
    }
    return { hh, mm, ss };
  }

  function drawAnalogClock(ctx, size, time, cfg) {
    const { hh, mm, ss, ms } = time;

    const cx = size / 2, cy = size / 2;
    const r = Math.min(cx, cy) * 0.92;

    ctx.clearRect(0, 0, size, size);

    // Face
    ctx.beginPath();
    ctx.arc(cx, cy, r, 0, Math.PI * 2);
    ctx.lineWidth = Math.max(2, size * 0.02);
    ctx.strokeStyle = "rgba(0,0,0,0.35)";
    ctx.stroke();

    // Ticks
    for (let i = 0; i < 60; i++) {
      const ang = (i * Math.PI) / 30;
      const isHour = i % 5 === 0;
      const inner = r * (isHour ? 0.82 : 0.87);
      const outer = r * 0.95;

      ctx.beginPath();
      ctx.lineWidth = isHour ? Math.max(2, size * 0.02) : Math.max(1, size * 0.01);
      ctx.strokeStyle = "rgba(0,0,0,0.45)";
      ctx.moveTo(cx + inner * Math.sin(ang), cy - inner * Math.cos(ang));
      ctx.lineTo(cx + outer * Math.sin(ang), cy - outer * Math.cos(ang));
      ctx.stroke();
    }

    const sec = cfg.smooth ? (ss + (ms / 1000)) : ss;
    const min = mm + sec / 60;
    const hour = (hh % 12) + min / 60;

    const aSec = (sec * Math.PI) / 30;
    const aMin = (min * Math.PI) / 30;
    const aHour = (hour * Math.PI) / 6;

    function hand(angle, length, width, color) {
      ctx.save();
      ctx.beginPath();
      ctx.lineWidth = width;
      ctx.lineCap = "round";
      ctx.strokeStyle = color;
      ctx.moveTo(cx, cy);
      ctx.lineTo(cx + length * Math.sin(angle), cy - length * Math.cos(angle));
      ctx.stroke();
      ctx.restore();
    }

    hand(aHour, r * 0.55, Math.max(3, size * 0.035), "rgba(0,0,0,0.75)");
    hand(aMin,  r * 0.75, Math.max(2, size * 0.025), "rgba(0,0,0,0.65)");
    hand(aSec,  r * 0.82, Math.max(1, size * 0.012), "rgba(200,0,0,0.75)");

    // Center pin
    ctx.beginPath();
    ctx.arc(cx, cy, Math.max(3, size * 0.02), 0, Math.PI * 2);
    ctx.fillStyle = "rgba(0,0,0,0.7)";
    ctx.fill();
  }

  function drawErrorOnCanvas(canvas, msg) {
    const ctx = canvas.getContext("2d");
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    ctx.font = "14px system-ui, -apple-system, Segoe UI, Roboto, Arial";
    ctx.fillStyle = "rgba(0,0,0,0.75)";
    ctx.textAlign = "center";
    ctx.fillText(msg, canvas.width / 2, canvas.height / 2);
  }

  /** ---------- Init ---------- */

  function initClockWrapper(wrapper) {
    const cfgRaw = safeParseJSON(wrapper.getAttribute("data-mtc")) || {};
    const cfg = {
      format12: cfgRaw.format === "12",
      seconds: cfgRaw.seconds !== "0",
      showDate: cfgRaw.show_date === "1",
      smooth: cfgRaw.smooth === "1",
      showTz: cfgRaw.show_tz !== "0",
    };

    const clockEl = wrapper.querySelector(".mtc-clock");
    if (!clockEl) return;

    const kind = clockEl.getAttribute("data-kind") || "analog";
    const tz = clockEl.getAttribute("data-tz") || "";

    // TZ label
    const tzEl = clockEl.querySelector(".mtc-tz");
    if (tzEl) {
      tzEl.style.display = cfg.showTz ? "" : "none";
      if (cfg.showTz && isLocalTZ(tz)) tzEl.textContent = resolvedLocalTZName();
    }

    // Digital date
    const dateEl = clockEl.querySelector(".mtc-date");
    if (dateEl) dateEl.hidden = !cfg.showDate;

    let digitalFormatter = null;

    function tick() {
      const now = new Date();

      if (kind === "digital") {
        const timeEl = clockEl.querySelector(".mtc-time");
        const dEl = clockEl.querySelector(".mtc-date");
        if (!timeEl) return;

        try {
          if (!digitalFormatter) digitalFormatter = makeDigitalFormatter(tz, cfg);
          const { time, date } = formatDigitalParts(digitalFormatter, now);
          timeEl.textContent = time;
          if (cfg.showDate && dEl) dEl.textContent = date;
        } catch (e) {
          timeEl.textContent = "Invalid timezone";
          if (dEl) dEl.textContent = "";
        }
      } else {
        const canvas = clockEl.querySelector("canvas");
        if (!canvas) return;

        try {
          const parts = getTimePartsInTZ(tz, now);
          drawAnalogClock(canvas.getContext("2d"), canvas.width, { ...parts, ms: now.getMilliseconds() }, cfg);
        } catch (e) {
          drawErrorOnCanvas(canvas, "Invalid timezone");
        }
      }
    }

    // Smooth mode only for analog (digital stays 1Hz)
    let rafId = null;
    let timer = null;

    if (cfg.smooth && kind === "analog") {
      const loop = () => {
        tick();
        rafId = requestAnimationFrame(loop);
      };
      loop();
    } else {
      const scheduleNext = () => {
        const ms = 1000 - (Date.now() % 1000);
        timer = setTimeout(() => {
          tick();
          scheduleNext();
        }, ms);
      };
      tick();
      scheduleNext();
    }

    const obs = new MutationObserver(() => {
      if (!document.body.contains(wrapper)) {
        if (rafId) cancelAnimationFrame(rafId);
        if (timer) clearTimeout(timer);
        obs.disconnect();
      }
    });
    obs.observe(document.body, { childList: true, subtree: true });
  }

  function initAll() {
    document.querySelectorAll(".mtc-wrap[data-mtc]").forEach(initClockWrapper);
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initAll);
  } else {
    initAll();
  }
})();