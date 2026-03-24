// ===================== IMPORT MODULE ======================
const {
  makeWASocket,
  useMultiFileAuthState,
  fetchLatestBaileysVersion,
} = require("@whiskeysockets/baileys");

const pino = require("pino");
const chalk = require("chalk");
const axios = require("axios");
const readline = require("readline");

let notifInterval = null;

// ===================== API CONFIG ======================
const API_BASE_URL = "http://localhost:8000/api";
const BACKEND_TICKET_ENDPOINT = `${API_BASE_URL}/tickets/wa`;

// ===================== WA NOTIFICATION API ======================
const WA_PENDING_NOTIF = `${API_BASE_URL}/wa/pending-notifications`;
const WA_SENT_NOTIF = `${API_BASE_URL}/wa/notification-sent`;

// ===================== CEK STATUS API ======================
const WA_TICKET_STATUS = `${API_BASE_URL}/wa/ticket-status`;

// ===================== STATE MEMORY ======================
const helpShown = {};
const userStates = {};
const TIMEOUT_MINUTES = 15 * 60 * 1000; // 15 menit timeout

// Tetap pairing code
const usePairingCode = true;

// ===================== PROMPT ======================
async function question(promt) {
  process.stdout.write(promt);
  const rl = readline.createInterface({
    input: process.stdin,
    output: process.stdout,
  });
  return new Promise((resolve) =>
    rl.question("", (ans) => {
      rl.close();
      resolve(ans);
    }),
  );
}

// ===================== STATE CONTROL ======================
function setUserState(phone, state, selected = null) {
  userStates[phone] = { state, selected, startedAt: Date.now() };
}
function clearUserState(phone) {
  delete userStates[phone];
}
function getUserState(phone) {
  return userStates[phone] || { state: "idle", selected: null };
}
function checkTimeout(phone) {
  const st = getUserState(phone);
  if (st.state === "awaiting_form") {
    if (Date.now() - st.startedAt >= TIMEOUT_MINUTES) {
      clearUserState(phone);
      return true;
    }
  }
  return false;
}

// ===================== SEND TEXT ======================
async function sendTextSimple(sock, jid, text) {
  try {
    await sock.sendMessage(jid, { text });
  } catch (e) {}
}

// ===================== GREETING ======================
function greetingMessage(pushname = "User") {
  return `Terima kasih telah menghubungi PUSKOM WICIDA 🙏
Silahkan pilih menu layanan helpdesk berikut:
1. WeLearn 
2. SIAK
3. Email Wicida
4. Layanan Akademik (KPST, KRS, dll)
5. Lainnya.
6. Cek Status Tiket

🕗WAKTU OPERASIONAL🕔
Senin - Kamis
08:30 - 12:00 | 13:30 - 16:00
Jum'at
08:30 - 11:00 | 14:00 - 16:00

Halo *${pushname}*, ketik nomor layanan atau 'Pengaduan <Unit>' untuk melanjutkan.`;
}

// ===================== AKADEMIK SUBMENU ======================
async function sendAkademikMenu(sock, jid) {
  await sendTextSimple(
    sock,
    jid,
    `Silakan pilih unit Pengaduan:
1. KPST
2. KRS

Balas dengan nomor (contoh: 1) atau tulis 'KPST' untuk memilih.`,
  );
}

// ===================== FORM TEMPLATE ======================
function formTemplateFor(flow) {
  switch (flow) {
    case "SIak":
      return `Pengaduan SIAK\nNama:\nNIM:\nLaporan:`;
    case "KPST":
      return `Pengaduan KPST\nNama:\nNIM:\nKeluhan / Detail:`;
    case "WeLearn":
      return `Pengaduan WeLearn\nNama:\nNIM:\nPermintaan:`;
    case "Email":
      return `Pengaduan Email Wicida\nNama:\nNIM:\nEmail Wicida:\nMasalah:`;
    case "KRS":
      return `Pengaduan KRS\nNama:\nNIM:\nKeluhan / Detail:`;
    case "Puskom":
      return `Pengaduan Puskom\nNama:\nUnit/Bagian:\nKeluhan / Detail:`;
    default:
      return "";
  }
}

// ===================== FORMAT STATUS ======================
function formatStatus(status) {
  const s = status.toLowerCase();

  if (s === "open") return "🔵 Open";
  if (s === "diproses") return "🟡 Diproses";
  if (s === "selesai") return "🟢 Selesai";
  if (s === "ditolak") return "🔴 Ditolak";

  return status;
}

// ===================== FORM DETECTOR ======================
function parseFullForm(text) {
  if (!text) return null;
  const t = text.replace(/\r/g, "").trim();

  const rules = [
    { key: "SIak", re: /^(pendaftaran|pengaduan)\s*siak/i },
    { key: "KPST", re: /^(pendaftaran|pengaduan)\s*kpst/i },
    { key: "WeLearn", re: /^(pendaftaran|pengaduan)\s*welearn/i },
    { key: "Email", re: /^(pendaftaran|pengaduan)\s*email/i },
    { key: "KRS", re: /^(pendaftaran|pengaduan)\s*krs/i },
    { key: "Puskom", re: /^pengaduan\s*puskom/i },
  ];

  for (const r of rules) {
    if (r.re.test(t)) {
      const name = (t.match(/nama\s*:\s*(.+)/i) || [null, null])[1];
      const nim = (t.match(/nim\s*:\s*(.+)/i) || [null, null])[1];
      return { flow: r.key, name, nim, text: t };
    }
  }
  return null;
}

// ===================== CREATE TICKET API ======================
async function createTicketFromBot(phone, jid, name, nim, unit, message) {
  const res = await axios.post(BACKEND_TICKET_ENDPOINT, {
    phone,
    wa_jid: jid,
    name,
    nim,
    unit,
    message,
  });
  return res.data?.data?.ticket;
}

// ===================== GET TICKET STATUS ======================
async function getTicketStatus(code) {
  try {
    const res = await axios.get(`${WA_TICKET_STATUS}/${code}`);
    return res.data?.ticket || null;
  } catch (err) {
    console.log("Ticket status error:", err.message);
    return null;
  }
}

async function getMyTickets(jid) {
  try {
    const res = await axios.post(`${API_BASE_URL}/wa/my-tickets`, {
      wa_jid: jid,
    });

    return res.data?.tickets || [];
  } catch (err) {
    console.log("My tickets error:", err.message);
    return [];
  }
}

// ===================== PROCESS WA NOTIFICATIONS ======================
async function processPendingNotifications(sock) {
  try {
    const res = await axios.get(WA_PENDING_NOTIF);
    const notifications = res.data?.data || [];

    for (const notif of notifications) {
      if (!notif.wa_jid) continue;

      await sock.sendMessage(notif.wa_jid, {
        text: notif.message,
      });

      const safeId = encodeURIComponent(String(notif.id).trim());
      await axios.post(`${WA_SENT_NOTIF}/${safeId}`);
    }
  } catch (err) {
    console.log("❌ WA Notification Error:", err.message);
  }
}

// ===================== MAIN BOT ======================
async function connectToWhatsApp() {
  const { state, saveCreds } = await useMultiFileAuthState("./PuskomSesi");

  let waVersion;
  try {
    waVersion = (await fetchLatestBaileysVersion()).version;
  } catch {
    waVersion = undefined;
  }

  const puskom = makeWASocket({
    logger: pino({ level: "silent" }),
    printQRInTerminal: !usePairingCode,
    auth: state,
    browser: ["Ubuntu", "Chrome", "20.0.04"],
    version: waVersion,
    syncFullHistory: true,
  });

  // ★ pairing code
  if (usePairingCode && !puskom.authState.creds.registered) {
    const phone = await question("☘️ Masukan Nomor Yang Diawali Dengan 62 :\n");
    const code = await puskom.requestPairingCode(phone.trim());
    console.log("🎁 Pairing Code:", code);
  }

  puskom.ev.on("creds.update", saveCreds);

  puskom.ev.on("connection.update", (u) => {
    if (u.connection === "open") {
      console.log("✔ Bot Terhubung");

      if (!notifInterval) {
        notifInterval = setInterval(() => {
          processPendingNotifications(puskom);
        }, 10000);
      }
    }

    if (u.connection === "close") {
      console.log("❌ Terputus, mencoba ulang...");
      connectToWhatsApp();
    }
  });

  // ===================== MESSAGE HANDLER ======================
  puskom.ev.on("messages.upsert", async (m) => {
    const msg = m.messages[0];
    if (!msg?.message) return;
    if (msg.key.fromMe) return;

    const sender = msg.key.remoteJid;
    const phone = sender.replace("@s.whatsapp.net", "");
    const pushname = msg.pushName || "User";

    const text =
      msg.message.conversation || msg.message.extendedTextMessage?.text || "";
    const t = text.trim();

    // cek status handler
    // ===================== CEK STATUS TIKET ======================
    if (/^(status|cek)/i.test(t)) {
      const parts = t.split(" ");
      const code = parts[1]?.toUpperCase();

      if (!code) {
        await sendTextSimple(
          puskom,
          sender,
          "Format salah.\nContoh: status KP-20260312-A8D4F2",
        );
        return;
      }

      await sendTextSimple(puskom, sender, "🔎 Mengecek status tiket...");

      const ticket = await getTicketStatus(code);

      if (!ticket) {
        await sendTextSimple(puskom, sender, "❌ Tiket tidak ditemukan.");
        return;
      }

      await sendTextSimple(
        puskom,
        sender,
        `📄 *DETAIL TIKET*

━━━━━━━━━━━━━━
📌 Kode   : *${ticket.code}*
📝 Pelapor: ${ticket.title}
📊 Status : ${formatStatus(ticket.status)}
━━━━━━━━━━━━━━

Ketik *menu* untuk kembali ke menu utama.`,
      );

      return;
    }
    // ===================== Tiket Saya command ======================
    if (/^(tiket saya|tiketku)$/i.test(t)) {
      await sendTextSimple(puskom, sender, "🔎 Mengecek tiket anda...");

      const tickets = await getMyTickets(sender);

      if (!tickets.length) {
        await sendTextSimple(puskom, sender, "Anda belum memiliki tiket.");
        return;
      }

      let msg = `📄 *TIKET ANDA*\n`;
      msg += `Total tiket: *${tickets.length}*\n\n`;

      tickets.forEach((ticket, i) => {
        msg += `🔹 *Tiket ${i + 1}*\n`;
        msg += `📌 Kode    : *${ticket.code}*\n`;
        msg += `👤 Pelapor : ${ticket.title}\n`;
        msg += `📊 Status  : *${formatStatus(ticket.status)}*\n`;
        msg += `━━━━━━━━━━━━━━\n`;
      });

      msg += "\nKetik *status KODE_TIKET* untuk melihat detail.";

      await sendTextSimple(puskom, sender, msg);
      return;
    }

    // timeout handler
    if (checkTimeout(phone)) {
      await sendTextSimple(
        puskom,
        sender,
        "⏳ Sesi pengaduan berakhir (15 menit). Ketik *menu* untuk mulai ulang.",
      );
    }

    const state = getUserState(phone);

    // ========== awaiting_akademik ==========
    if (state.state === "awaiting_akademik") {
      if (t === "1" || /^kpst$/i.test(t)) {
        setUserState(phone, "awaiting_form", "KPST");
        await sendTextSimple(
          puskom,
          sender,
          formTemplateFor("KPST") + "\n\nKetik 'batal' jika salah.",
        );
        return;
      }

      if (t === "2" || /^krs$/i.test(t)) {
        setUserState(phone, "awaiting_form", "KRS");
        await sendTextSimple(
          puskom,
          sender,
          formTemplateFor("KRS") + "\n\nKetik 'batal' jika salah.",
        );
        return;
      }

      await sendTextSimple(
        puskom,
        sender,
        "Pilihan tidak valid.\nBalas *1* untuk KPST atau *2* untuk KRS.",
      );
      return;
    }

    // ========== awaiting_form ==========
    if (state.state === "awaiting_form") {
      if (/^batal$/i.test(t)) {
        clearUserState(phone);
        await sendTextSimple(
          puskom,
          sender,
          "Proses dibatalkan. Kembali ke menu.",
        );
        await sendTextSimple(puskom, sender, greetingMessage(pushname));
        return;
      }

      const parsed = parseFullForm(t);
      if (parsed) {
        await sendTextSimple(puskom, sender, "Sedang membuat tiket...");
        const ticket = await createTicketFromBot(
          phone,
          sender,
          parsed.name || pushname,
          parsed.nim || null,
          parsed.flow,
          parsed.text,
        );

        await sendTextSimple(
          puskom,
          sender,
          `✅ *Tiket Berhasil Dibuat*

📌 Kode Tiket
${ticket.code}

📊 Status : 🟡 Open

Untuk cek status kirim:
*status ${ticket.code}*

Terima kasih telah menghubungi PUSKOM WICIDA 🙏`,
        );
        clearUserState(phone);
        return;
      }

      return; // ignore hanya chat biasa
    }

    // ========== greeting ==========
    if (
      /^(halo|hi|hello|menu|selamat pagi|selamat siang|selamat sore)$/i.test(t)
    ) {
      await sendTextSimple(puskom, sender, greetingMessage(pushname));
      return;
    }

    // ========== MAIN MENU ==========
    if (/^[1-6]$/.test(t)) {
      if (t === "1") {
        setUserState(phone, "awaiting_form", "WeLearn");
        await sendTextSimple(
          puskom,
          sender,
          formTemplateFor("WeLearn") + "\n\nKetik 'batal' jika salah.",
        );
        return;
      }
      if (t === "2") {
        setUserState(phone, "awaiting_form", "SIak");
        await sendTextSimple(
          puskom,
          sender,
          formTemplateFor("SIak") + "\n\nKetik 'batal' jika salah.",
        );
        return;
      }
      if (t === "3") {
        setUserState(phone, "awaiting_form", "Email");
        await sendTextSimple(
          puskom,
          sender,
          formTemplateFor("Email") + "\n\nKetik 'batal' jika salah.",
        );
        return;
      }
      if (t === "4") {
        setUserState(phone, "awaiting_akademik", null);
        await sendAkademikMenu(puskom, sender);
        return;
      }
      if (t === "5") {
        setUserState(phone, "awaiting_form", "Puskom");
        await sendTextSimple(
          puskom,
          sender,
          formTemplateFor("Puskom") + "\n\nKetik 'batal' jika salah.",
        );
        return;
      }
      if (t === "6") {
        await sendTextSimple(
          puskom,
          sender,
          `📄 *CEK STATUS TIKET*

Untuk mengecek status tiket, Anda bisa:

1️⃣ Kirim:
status KODE_TIKET

Contoh:
status KP-20260312-A8D4F2

2️⃣ Atau ketik:
*Tiket Saya*`,
        );
        return;
      }
    }

    // fallback
    await sendTextSimple(
      puskom,
      sender,
      `Halo *${pushname}*, ketik *menu* untuk memulai.`,
    );
  });
}

connectToWhatsApp();
