# Real-time Monitoring System with Laravel

Senior Laravel Developer Interview Task Implementation

A comprehensive real-time Laravel application demonstrating advanced features including broadcasting, queue management, monitoring capabilities, and Telegram alerting through Grafana.

## 🚀 Project Overview

This project showcases enterprise-level Laravel features through a real-time chat application with comprehensive monitoring, queue management, and alert notification systems using Docker Sail and Laravel 12.

## 📋 Features Overview

### ✅ Section 1: Real-time Broadcasting

- **Real-time Event Broadcasting** with Laravel Reverb
- **Presence Channels** for active user tracking
- **MessageSent Event** broadcasting
- **POST /notify Endpoint** with validation
- **Laravel defer()** method optimization
- **Livewire Frontend** with Tailwind CSS


### ✅ Section 2: Queue Management with Horizon

- **Laravel Horizon** configuration and monitoring
- **Multiple Queue Workers** (default + broadcasts)
- **ProcessBroadcastMessage Job** processing
- **High-throughput Configuration** with auto-scaling
- **Secure Dashboard** with authentication


### ✅ Section 3: Laravel Pulse Integration

- **Request Throughput** monitoring
- **Broadcast Events** tracking
- **Queue Jobs** and processing times
- **Slow Database Queries** detection
- **Cache Hit/Miss Ratios** monitoring
- **Production-ready** configuration


### ✅ Section 4: Prometheus \& Grafana with Telegram Alerts

- **Prometheus** metrics collection from Laravel
- **Grafana** dashboards and visualization
- **Telegram Alerting** for critical issues
- **Comprehensive Monitoring** panels
- **Alert Threshold** configuration


## 🛠️ Technology Stack

| Component | Technology |
| :-- | :-- |
| **Framework** | Laravel 12 |
| **Broadcasting** | Laravel Reverb |
| **Queue Management** | Laravel Horizon + Redis |
| **Monitoring** | Laravel Pulse |
| **Metrics Collection** | Prometheus |
| **Visualization** | Grafana |
| **Alerting** | Telegram Notifications |
| **Frontend** | Livewire + Tailwind CSS |
| **Database** | MySQL |
| **Cache** | Redis |
| **Containerization** | Docker Sail |

## 🚦 Prerequisites

Before running this project, ensure you have:

- **Docker** and **Docker Compose** installed
- **Git** for cloning the repository
- **Telegram Account** for alert notifications
- Minimum 4GB RAM and 2GB free disk space


## 📥 Installation \& Setup

### 1. Clone the Repository

```bash
git clone https://github.com/davidchemwetich/realtime-monitoing-system.git
cd realtime-monitoing-system
```


### 2. Install Dependencies

```bash
# Install PHP dependencies
./vendor/bin/sail composer install

# Install Node.js dependencies
./vendor/bin/sail npm install
```


### 3. Environment Configuration

```bash
# Copy environment file
cp .env.example .env

# Generate application key
./vendor/bin/sail artisan key:generate
```


### 4. Configure Environment Variables

Update your `.env` file with the following configurations:

```env
# Application
APP_NAME=Laravel
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

# Database
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=sail
DB_PASSWORD=password

# Queue & Broadcasting
QUEUE_CONNECTION=redis
BROADCAST_CONNECTION=reverb
REDIS_HOST=redis

# Reverb Configuration
REVERB_APP_ID=682265
REVERB_APP_KEY=jnjruzvlhp98079lbxuh
REVERB_APP_SECRET=ijr6qniw2isfygbgqqpc
REVERB_HOST="localhost"
REVERB_PORT=8080
REVERB_SCHEME=http

# Laravel Pulse Configuration
PULSE_ENABLED=true
PULSE_PATH=pulse
PULSE_STORAGE_DRIVER=database
PULSE_CACHE_INTERACTIONS_ENABLED=true
PULSE_SLOW_QUERIES_THRESHOLD=500
PULSE_SLOW_REQUESTS_THRESHOLD=1000
```


### 5. Set Up Telegram Bot (Required for Alerts)

1. **Create a Telegram Bot:**
    - Message `@BotFather` on Telegram
    - Send `/newbot` command
    - Choose bot name and username
    - **Save the Bot Token**
2. **Get Your Chat ID:**
    - Message your new bot with any text
    - Visit: `https://api.telegram.org/botYOUR_BOT_TOKEN/getUpdates`
    - Find your `chat_id` in the JSON response
3. **Update Grafana Configuration:**
    - Replace `YOUR_TELEGRAM_BOT_TOKEN` and `YOUR_CHAT_ID` in:
        - `docker/grafana/provisioning/notifiers/telegram.yml`
        - `docker/grafana/provisioning/alerting/notification-policies.yml`

### 6. Create Required Configuration Files

Create the monitoring configuration files:

```bash
# Create directories
mkdir -p docker/{prometheus,grafana/{provisioning/{datasources,dashboards,notifiers,alerting},dashboards}}

# Prometheus configuration files are included in the repository
# Grafana configuration files are included in the repository
# Telegram configuration needs your bot token and chat ID
```


### 7. Database Setup

```bash
# Start database service
./vendor/bin/sail up -d mysql redis

# Run migrations
./vendor/bin/sail artisan migrate

# Seed database (optional)
./vendor/bin/sail artisan db:seed
```


## 🚀 Running the Application

### Start All Services

```bash
# Start all Docker services (Laravel, MySQL, Redis, Prometheus, Grafana)
./vendor/bin/sail up -d

# Start Laravel services in separate terminals:

# Terminal 1: Laravel Reverb (Broadcasting)
./vendor/bin/sail artisan reverb:start

# Terminal 2: Laravel Horizon (Queue Management)
./vendor/bin/sail artisan horizon

# Terminal 3: Frontend Assets (Development)
./vendor/bin/sail npm run dev

# Terminal 4: Laravel Application (if not using Docker web service)
./vendor/bin/sail artisan serve
```


### Alternative: Using Process Manager

For production-like environment, use a process manager:

```bash
# Start all services with Docker Compose
./vendor/bin/sail up -d

# Services will auto-start:
# - Laravel App: http://localhost
# - Prometheus: http://localhost:9090
# - Grafana: http://localhost:3000
```


## 📱 Access Points

| Service | URL | Credentials |
| :-- | :-- | :-- |
| **Chat Application** | http://localhost/chat | Register/Login required |
| **Laravel Dashboard** | http://localhost/dashboard | Register/Login required |
| **Horizon Queue Dashboard** | http://localhost/horizon | Admin access |
| **Pulse Monitoring** | http://localhost/pulse | Admin access |
| **Prometheus Metrics** | http://localhost:9090 | No auth |
| **Grafana Dashboards** | http://localhost:3000 | admin/admin123 |
| **Laravel Metrics Endpoint** | http://localhost/metrics | No auth |

## 🔧 Testing the Application

### 1. Test Real-time Chat

```bash
# Register two users in different browsers
# Navigate to http://localhost/chat
# Send messages and verify real-time updates
```


### 2. Test Queue Processing

```bash
# Monitor Horizon dashboard
# Send messages to trigger queue jobs
# Check job processing in real-time
```


### 3. Test Monitoring

```bash
# Access Pulse dashboard for real-time metrics
# Check Prometheus metrics: curl http://localhost/metrics
# View Grafana dashboards for comprehensive monitoring
```


### 4. Test Telegram Alerts

```bash
# Generate test load to trigger alerts
for i in {1..60}; do
  curl -X POST http://localhost/notify \
    -H "Content-Type: application/json" \
    -H "X-CSRF-TOKEN: $(curl -s http://localhost | grep csrf | cut -d'"' -f4)" \
    -d '{"message":"Test message '$i'"}' &
done

# Check Telegram for alert notifications
```


## 📊 Monitoring \& Alerting

### Grafana Dashboards Include:

- **Requests per minute** and response times
- **Broadcasts per minute** from Reverb
- **Job processing rates** and durations
- **Queue lengths** and processing backlogs
- **Application memory usage**
- **Cache effectiveness** metrics


### Telegram Alerts Configured For:

- **High queue backlog** (>100 jobs warning, >500 critical)
- **Slow response times** (>10 slow requests/5min)
- **Error rate spikes** (>5 exceptions/5min)
- **Database connection issues** (<1 active connection)
- **High memory usage** (>128MB warning, >256MB critical)
- **Low cache effectiveness** (<70% hit rate)


### Alert Thresholds Rationale:

| **Alert** | **Warning** | **Critical** | **Rationale** |
| :-- | :-- | :-- | :-- |
| **Queue Backlog** | >100 jobs | >500 jobs | Based on processing capacity with 3 workers |
| **Response Time** | >10 slow/5min | >20 slow/5min | >2% slow requests indicate performance issues |
| **Memory Usage** | >128MB | >256MB | Prevents OOM while allowing normal operation |
| **Error Rate** | >5/5min | >10/5min | Healthy apps have <0.1% error rate |
| **Cache Hit Rate** | <70% | <50% | Good cache achieves 80%+ effectiveness |

## 🔒 Security Considerations

### Dashboard Access

- **Horizon:** Restricted to authorized emails
- **Pulse:** Authentication required
- **Grafana:** Admin credentials required


### Production Deployment

```bash
# Update environment variables for production
APP_ENV=production
APP_DEBUG=false

# Configure proper database credentials
# Set up SSL certificates
# Configure firewall rules
# Set up backup strategies
```


## 🛠️ Troubleshooting

### Common Issues:

1. **Docker Services Not Starting:**
```bash
# Check Docker status
docker ps
# Restart services
./vendor/bin/sail down && ./vendor/bin/sail up -d
```

2. **Prometheus Cannot Scrape Metrics:**
```bash
# Test metrics endpoint
curl http://localhost/metrics
# Check Docker network connectivity
./vendor/bin/sail exec prometheus wget -qO- http://laravel.test/metrics
```

3. **Grafana Cannot Connect to Prometheus:**
```bash
# Check Prometheus data source configuration
# Verify URL: http://prometheus:9090
# Test connectivity from Grafana container
```

4. **Telegram Alerts Not Working:**
```bash
# Test bot token
curl -X GET "https://api.telegram.org/botYOUR_BOT_TOKEN/getMe"
# Test message sending
curl -X POST "https://api.telegram.org/botYOUR_BOT_TOKEN/sendMessage" \
  -d "chat_id=YOUR_CHAT_ID&text=Test"
```


### Logs and Debugging:

```bash
# View application logs
./vendor/bin/sail logs laravel.test -f

# View specific service logs
./vendor/bin/sail logs prometheus -f
./vendor/bin/sail logs grafana -f

# Laravel application logs
./vendor/bin/sail artisan log:tail
```


## 📈 Performance Optimization

### Production Recommendations:

- Configure **Redis** for session and cache storage
- Set up **queue workers** with supervisord
- Enable **OpCache** for PHP optimization
- Configure **database connection pooling**
- Set up **CDN** for static assets



