# 🎫 Support Ticket System

Fullstack helpdesk system featuring ticket management, WhatsApp bot integration, and admin dashboard. Designed for real-world support workflows.

---

## 🚀 Tech Stack

- **Backend:** Laravel (PHP)
- **Frontend:** Vue.js + Vite
- **Bot:** Node.js (Baileys WhatsApp API)

---

## ✨ Features

### 🧑‍💼 Admin Dashboard

- Manage incoming tickets
- View ticket status & history
- Reply to user tickets
- Notification system

### 🎫 Ticketing System

- Create & manage support tickets
- Ticket categories & priorities
- Status tracking (open, process, closed)

### 🤖 WhatsApp Bot

- Auto response menu
- Ticket creation via WhatsApp
- Notification to users
- Session-based interaction

---

## 📁 Project Structure (Monorepo)

```
support-ticket-system/
├── Backend/     # Laravel API
├── Frontend/    # Vue.js client
└── Bot/         # WhatsApp bot (Node.js)
```

---

## ⚙️ Installation & Setup

### 1. Clone Repository

```bash
git clone https://github.com/Doramidesu/support-ticket-system.git
cd support-ticket-system
```

---

### 2. Setup Backend (Laravel)

```bash
cd Backend
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate
php artisan serve
```

---

### 3. Setup Frontend (Vue)

```bash
cd Frontend
npm install
npm run dev
```

---

### 4. Setup WhatsApp Bot

```bash
cd Bot
npm install
node index.js
```

---

## 🔐 Environment Variables

Sensitive data is stored in `.env` files and **not included in the repository**.

Example:

```
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

---

## 🧠 Notes

- Ensure backend is running before frontend
- WhatsApp bot requires authentication (pairing code)

---

## 📌 Future Improvements

- Role-based access control
- Real-time notifications (WebSocket)
- Deployment setup (Docker / VPS)
- UI/UX improvements

---

## 👨‍💻 Author

Developed by **Doramidesu**

---

## ⭐ Support

If you like this project, don't forget to give it a ⭐ on GitHub!
