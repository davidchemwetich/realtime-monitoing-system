# Real-time Monitoring System with Laravel Pulse

[![Laravel](https://img.shields.io/badge/Laravel-11.x-red.svg)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2+-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

A comprehensive real-time Laravel application demonstrating advanced Laravel features including broadcasting with presence channels, queue management with Horizon, deferred execution, and robust monitoring capabilities using Laravel Pulse.

## 🚀 Features

- **Real-time Chat System** with Laravel Reverb broadcasting
- **Presence Channels** to track active users in real-time
- **Queue Management** with Laravel Horizon
- **Advanced Monitoring** with Laravel Pulse
- **Deferred Execution** for performance optimization
- **Modern UI** with Livewire and Tailwind CSS
- **Production-ready** configuration and monitoring

## 📋 Requirements

- PHP 8.2 or higher
- Composer
- Node.js & NPM
- Docker & Docker Compose (for Laravel Sail)
- Ubuntu 20.04+ (recommended)

## 🛠️ Installation

### 1. Clone the Repository

```bash
git clone https://github.com/davidchemwetich/realtime-monitoring-system.git
cd realtime-monitoring-system
```

### 2. Install Dependencies

```bash
# Install PHP dependencies
composer install

# Install JavaScript dependencies
npm install
```

### 3. Environment Setup

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### 4. Configure Environment Variables

Update your `.env` file with the following configuration:

```env
# Application
APP_NAME="Realtime Monitoring System"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

# Database (Sail configuration)
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=sail
DB_PASSWORD=password

# Queue Configuration
QUEUE_CONNECTION=redis

# Cache Configuration
CACHE_STORE=database

# Redis Configuration
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

# Broadcasting with Reverb
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=682265
REVERB_APP_KEY=jnjruzvlhp98079lbxuh
REVERB_APP_SECRET=ijr6qniw2isfygbgqqpc
REVERB_HOST="localhost"
REVERB_PORT=8080
REVERB_SCHEME=http

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"

# Laravel Pulse Configuration
PULSE_ENABLED=true
PULSE_STORAGE_DRIVER=database
PULSE_INGEST_DRIVER=storage

# Pulse Monitoring Thresholds
PULSE_SLOW_QUERIES_THRESHOLD=500
PULSE_SLOW_JOBS_THRESHOLD=1000
PULSE_SLOW_REQUESTS_THRESHOLD=1000
PULSE_CACHE_INTERACTIONS_THRESHOLD=100

# Pulse Recorders
PULSE_CACHE_INTERACTIONS_ENABLED=true
PULSE_QUEUES_ENABLED=true
PULSE_SLOW_JOBS_ENABLED=true
PULSE_SLOW_QUERIES_ENABLED=true
PULSE_USER_REQUESTS_ENABLED=true
PULSE_BROADCAST_EVENTS_ENABLED=true
```

### 5. Start Laravel Sail

```bash
# Start Sail services (MySQL, Redis, etc.)
./vendor/bin/sail up -d

# Alternative: Add alias for convenience
alias sail='bash vendor/bin/sail'
sail up -d
```

### 6. Database Setup

```bash
# Run database migrations
sail artisan migrate

# Seed database with test users (optional)
sail artisan db:seed
```

### 7. Install Laravel Pulse

```bash
# Install Pulse
sail composer require laravel/pulse

# Publish Pulse assets
sail artisan vendor:publish --provider="Laravel\Pulse\PulseServiceProvider"

# Run Pulse migrations
sail artisan migrate
```

### 8. Build Frontend Assets

```bash
# Install and build frontend assets
sail npm install
sail npm run build

# For development with hot reload
sail npm run dev
```

## 🚀 Running the Application

### Start All Services

You'll need multiple terminal windows/tabs:

#### Terminal 1: Laravel Sail (Core Services)
```bash
sail up -d
```

#### Terminal 2: Laravel Reverb (Broadcasting)
```bash
sail artisan reverb:start --host=0.0.0.0 --port=8080
```

#### Terminal 3: Laravel Horizon (Queue Processing)
```bash
sail artisan horizon
```

#### Terminal 4: Frontend Development Server (Development only)
```bash
sail npm run dev
```

### Access the Application

- **Main Application**: http://localhost
- **Chat Interface**: http://localhost/chat
- **Pulse Monitoring**: http://localhost/pulse
- **Horizon Queue Dashboard**: http://localhost/horizon

## 👥 User Authentication

Create a test user to access the chat system:

```bash
# Create a user via tinker
sail artisan tinker

>>> $user = \App\Models\User::create([
...     'name' => 'Test User',
...     'email' => 'test@example.com',
...     'password' => bcrypt('password')
... ]);
>>> exit
```

Or register through the web interface at http://localhost/register

## 🧪 Testing the System

### 1. Test Real-time Chat
1. Open http://localhost/chat in multiple browser tabs/windows
2. Login with different users (or same user in incognito)
3. Send messages and observe real-time delivery
4. Check user presence indicators

### 2. Test Queue Processing
```bash
# Monitor queue jobs in Horizon
# Visit: http://localhost/horizon

# Generate test queue jobs
sail artisan tinker
>>> $user = \App\Models\User::first();
>>> \App\Jobs\ProcessBroadcastMessage::dispatch('Test message', $user);
>>> exit
```

### 3. Test Pulse Monitoring
```bash
# Generate test data for monitoring
sail artisan tinker

# Test cache operations
>>> for($i = 1; $i <= 10; $i++) { 
...   \Cache::put("test_key_$i", "value_$i", 3600); 
...   \Cache::get("test_key_$i");
... }

# Test slow database query
>>> \DB::select('SELECT SLEEP(2) as slow_query');

>>> exit
```

Then visit http://localhost/pulse to see the metrics.

## 📊 Monitoring Features

### Laravel Pulse Dashboard
Access comprehensive monitoring at http://localhost/pulse:

- **Request Throughput**: HTTP request metrics
- **Queue Performance**: Job processing times and status
- **Database Monitoring**: Slow query detection
- **Cache Analytics**: Hit/miss ratios and performance
- **Server Metrics**: CPU and memory usage
- **Exception Tracking**: Error monitoring and debugging
- **Custom Broadcast Events**: Real-time event monitoring

### Laravel Horizon Dashboard
Monitor queue jobs at http://localhost/horizon:

- **Job Processing**: Real-time job execution
- **Worker Management**: Queue worker status
- **Failed Jobs**: Error handling and retry management
- **Metrics**: Throughput and performance analytics

## 🔧 Development Commands

```bash
# Clear all caches
sail artisan optimize:clear

# Run tests
sail artisan test

# Check application status
sail artisan about

# View scheduled tasks
sail artisan schedule:list

# Clear Pulse data
sail artisan pulse:clear

# Restart Horizon workers
sail artisan horizon:terminate
sail artisan horizon
```

## 🛡️ Security

The application includes:

- **Authentication middleware** for protected routes
- **CSRF protection** for forms and AJAX requests
- **Input validation** for chat messages
- **Rate limiting** on API endpoints
- **Authorized access** to monitoring dashboards

## 🚀 Production Deployment

For production deployment, update these environment variables:

```env
# Production settings
APP_ENV=production
APP_DEBUG=false

# Optimized Pulse settings
PULSE_INGEST_DRIVER=redis
PULSE_CACHE_INTERACTIONS_SAMPLE_RATE=0.1
PULSE_USER_REQUESTS_SAMPLE_RATE=0.1
PULSE_SLOW_QUERIES_SAMPLE_RATE=0.1
```

Additional production steps:
```bash
# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Set up supervisor for queue workers
# Set up cron jobs for scheduled tasks
```