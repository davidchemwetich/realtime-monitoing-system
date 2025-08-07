
# Real-time Monitoring System with Laravel

> **Senior Laravel Developer Interview Task**  
> A comprehensive real-time Laravel application demonstrating advanced features including broadcasting, queue management, and monitoring capabilities.

---

## 🚀 Project Overview

This project showcases enterprise-level Laravel features through a real-time chat application with comprehensive monitoring and queue management systems.

---

## 📋 Section 1: Laravel Application with Advanced Broadcasting ✅
# Branch 1 - section-1-broadcasting
### **Functionality Implemented**
- ✅ **Real-time Event Broadcasting** with Laravel Reverb  
- ✅ **Presence Channels** for active user tracking  
- ✅ **MessageSent Event** broadcasting in real-time  
- ✅ **POST /notify Endpoint** with message validation  
- ✅ **Laravel `defer()`** method for performance optimization  
- ✅ **Livewire Frontend** with Tailwind CSS styling  
- ✅ **Real-time Message Display** and user presence updates  

### **Key Features**
- Real-time message broadcasting between users  
- Active users list with live presence detection  
- Message validation (max 500 characters)  
- Performance-optimized logging with `defer()`  
- Responsive UI with user avatars and timestamps  

### **Screenshot Required**
![Chat Interface](https://via.placeholder.com/800x400.png?text=Chat+Interface+with+Active+Users+and+Messages)

---

## ⚙️ Section 2: Queue Management with Horizon ✅
# Branch 2 - section-2-queue-management-horizon
### **Functionality Implemented**
- ✅ **Laravel Horizon** configured and secured  
- ✅ **Multiple Queue Workers** (default + broadcasts queues)  
- ✅ **ProcessBroadcastMessage Job** for queue processing  
- ✅ **High-throughput Configuration** with proper concurrency  
- ✅ **Queue Balancing** and retry mechanisms  
- ✅ **Horizon Dashboard** with authentication  

### **Key Features**
- Two supervisor configurations (production + local)  
- Auto-scaling strategy with appropriate concurrency limits  
- Failed job handling with retry logic  
- Message analytics processing in queues  
- Secure dashboard access with email-based authorization  

### **Screenshot Required**
![Horizon Dashboard](https://via.placeholder.com/800x400.png?text=Horizon+Dashboard+Queue+Metrics)

---

## 📊 Section 3: Laravel Pulse Integration ✅
# Branch 3 - feature/laravel-pulse-integration
### **Functionality Implemented**
- ✅ **Laravel Pulse** installed and configured  
- ✅ **Request Throughput** monitoring  
- ✅ **Broadcast Events** tracking with custom recorder  
- ✅ **Queue Jobs** and processing times monitoring  
- ✅ **Slow Database Queries** detection  
- ✅ **Cache Hit/Miss Ratios** with chat-specific keys  
- ✅ **Production-ready Configuration** with authentication  

### **Key Features**
- Custom `BroadcastEventRecorder` for `MessageSent` events  
- Real-time performance metrics dashboard  
- Configurable thresholds for performance alerts  
- Secure dashboard with role-based access  
- Automated data cleanup scheduling  

### **Screenshot Required**
![Pulse Dashboard](https://postimg.cc/MnDccLvM/800x400.png?text=Pulse+Dashboard+with+Metrics)

---

## 🛠️ Technology Stack

- **Framework:** Laravel 11  
- **Broadcasting:** Laravel Reverb  
- **Queue Management:** Laravel Horizon with Redis  
- **Monitoring:** Laravel Pulse  
- **Frontend:** Livewire + Tailwind CSS  
- **Database:** MySQL  
- **Cache:** Redis/Database  
- **Real-time:** WebSockets (Presence Channels)  

---

## 🚦 Quick Start (Development Environment)

```bash
# Clone repository
git clone https://github.com/davidchemwetich/realtime-monitoing-system.git
cd realtime-monitoring-system

# Install dependencies
./vendor/bin/sail composer install
./vendor/bin/sail npm install

# Setup environment
cp .env.example .env
./vendor/bin/sail artisan key:generate

# Database setup
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan db:seed

# Start services (in 4 separate terminals)
./vendor/bin/sail artisan serve          # Laravel app
./vendor/bin/sail artisan reverb:start   # Broadcasting
./vendor/bin/sail artisan horizon        # Queue management
./vendor/bin/sail npm run dev            # Frontend assets
```

---

## 📱 Access Points

- **Chat Application:** `http://localhost/chat`  
- **Horizon Dashboard:** `http://localhost/horizon`  
- **Pulse Monitoring:** `http://localhost/pulse`  

---

## 📸 Demo Screenshots

### Section 1: Real-time Chat  
![Chat Demo](https://via.placeholder.com/800x400.png?text=Real-time+Chat+Demo)

### Section 2: Horizon Queue Management  
![Horizon Demo](https://via.placeholder.com/800x400.png?text=Horizon+Queue+Metrics)

### Section 3: Pulse Monitoring  
![Pulse Demo](https://via.placeholder.com/800x400.png?text=Pulse+Monitoring+Dashboard)

---

**Status:** ✅ All sections implemented and functional in development environment
