# 🤖 WhatsApp Bot - Support Ticket System

This is the WhatsApp bot component of the Support Ticket System. It enables users to interact with the helpdesk via WhatsApp, create tickets, and receive real-time notifications.

---

## 🚀 Tech Stack

- Node.js
- Baileys (WhatsApp Web API)
- Axios
- Pino Logger

---

## ✨ Features

- 📩 Ticket creation via WhatsApp
- 🤖 Interactive menu system
- 🔄 Real-time notifications
- ⏱️ Session-based user flow
- ❌ Cancel form input (`batal` command)
- ⏳ Auto timeout (15 minutes for inactive sessions)

---

## ⚙️ Setup & Installation

```bash
npm install
node index.js
```

---

## 🔐 Authentication

- Uses WhatsApp pairing / QR login
- Session is stored locally in:

```bash
PuskomSesi/
```

> ⚠️ Do not share session files publicly

---

## 🔗 API Integration

The bot connects to the backend API for ticket creation.

Example endpoint:

```bash
http://localhost:8000/api
```

---

## 📁 Project Structure

```bash
Bot/
├── index.js        # Main bot entry point
├── botwa.js        # Bot logic / handler
├── PuskomSesi/     # Session storage
├── package.json
```

---

## 🧠 Bot Flow

1. User sends message
2. Bot displays menu options
3. User selects service (e.g., Pengaduan)
4. Bot collects form input
5. Data sent to backend API
6. Ticket is created
7. User receives confirmation

---

## ⚠️ Notes

- Ensure backend server is running before starting the bot
- Internet connection is required for WhatsApp connection
- Session will expire if deleted

---

## 📌 Future Improvements

- Multi-user session management
- Better validation system
- Rich message UI (buttons, lists)
- Deployment support

---

## 👨‍💻 Author

Part of the fullstack helpdesk system project
