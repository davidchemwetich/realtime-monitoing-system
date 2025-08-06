# Laravel Real-time Monitoring System - Quick Start Guide

This project is a Laravel 12-based real-time monitoring system using Sail, Livewire, and Reverb.

---

## 🔁 Fork or Clone & Run the Project

Follow these steps to fork or clone and run the Laravel Real-time Monitoring System:

### 🔨 1. Fork the Repository

- Visit the GitHub repository: [https://github.com/davidchemwetich/realtime-monitoing-system](https://github.com/davidchemwetich/realtime-monitoing-system)
- Click the **Fork** button (top-right)

### 💻 2. Clone Your Fork

```bash
git clone git@github.com:your-username/realtime-monitoing-system.git
cd realtime-monitoing-system
```

### 🐳 3. Start Laravel Sail

```bash
# Start Sail (will install dependencies if first time)
./vendor/bin/sail up -d
```

### 🧩 4. Install Dependencies

```bash
# Composer
./vendor/bin/sail composer install

# Node packages
./vendor/bin/sail npm install
./vendor/bin/sail npm run build
```

### ⚙️ 5. Set Up Environment

```bash
cp .env.example .env
./vendor/bin/sail artisan key:generate
```

Edit `.env` and set up your database and broadcasting settings as needed (see Reverb section above).

### 🛠 6. Run Migrations & Seeders (if any)

```bash
./vendor/bin/sail artisan migrate
```

### 🚀 7. Start the App & Reverb

```bash
./vendor/bin/sail up
./vendor/bin/sail artisan reverb:start
```

Visit [http://localhost](http://localhost) to access the app.

---

## 🖼️ Screenshots
<div style="padding:56.25% 0 0 0;position:relative;">
  <iframe 
    src="https://player.vimeo.com/video/1107694633?badge=0&autopause=0&player_id=0&app_id=58479&dnt=1" 
    frameborder="0" 
    allow="autoplay; fullscreen; picture-in-picture; clipboard-write; encrypted-media; web-share" 
    referrerpolicy="strict-origin-when-cross-origin" 
    style="position:absolute;top:0;left:0;width:100%;height:100%;" 
    title="344">
  </iframe>
</div>
<script src="https://player.vimeo.com/api/player.js"></script>

### Real-time Chat Interface

![Chat Interface](https://imgur.com/5qOlL9f.png)

### Active Users List

![Active Users](https://imgur.com/hcUFvfn.png)

---

✅ You’re now ready to use the Laravel real-time chat and monitoring system!
