# Laravel Real-Time Monitoring System - Complete Documentation

A comprehensive Laravel 12 application demonstrating enterprise-level real-time features including Reverb broadcasting, Horizon queue management, Laravel Pulse monitoring, Prometheus metrics, and Grafana alerting with rate limiting and health checks.

## 🚀 System Overview

This project demonstrates a production-ready Laravel application with comprehensive monitoring, real-time communication, and advanced queue management capabilities.

### Architecture Components

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Laravel App   │────│     Reverb      │────│   WebSocket     │
│  (Real-time)    │    │   Broadcasting  │    │   Connections   │
└─────────────────┘    └─────────────────┘    └─────────────────┘
         │                       │
         ▼                       ▼
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│     Horizon     │────│     Redis       │────│   Queue Jobs    │
│ Queue Monitor   │    │   Queue Store   │    │   Processing    │
└─────────────────┘    └─────────────────┘    └─────────────────┘
         │
         ▼
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Laravel Pulse │────│   Prometheus    │────│    Grafana      │
│   Monitoring    │    │   Metrics       │    │   Dashboard     │
└─────────────────┘    └─────────────────┘    └─────────────────┘
         │                       │                       │
         ▼                       ▼                       ▼
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│  Health Checks  │    │  Rate Limiting  │    │   Telegram      │
│   & Metrics     │    │   Protection    │    │   Alerts        │
└─────────────────┘    └─────────────────┘    └─────────────────┘
```


## 📋 Features Implemented

### ✅ Section 1: Real-time Broadcasting

- **Laravel Reverb** WebSocket server integration
- **Real-time chat system** with presence channels
- **Message broadcasting** with event-driven architecture
- **User presence tracking** and real-time updates
- **Optimized performance** with Laravel's `defer()` method


### ✅ Section 2: Queue Management with Horizon

- **Laravel Horizon** dashboard for queue monitoring
- **Multiple queue workers** with balanced processing
- **High-throughput configuration** for production
- **Job retry mechanisms** and failure handling
- **Queue balancing** and auto-scaling


### ✅ Section 3: Laravel Pulse Integration

- **Request throughput** monitoring
- **Broadcast events** tracking
- **Queue job processing** times
- **Slow database queries** detection
- **Cache hit/miss ratios** analysis


### ✅ Section 4: Prometheus \& Grafana

- **Metrics endpoint** exposing Pulse and Horizon data
- **Prometheus scraping** configuration
- **Grafana dashboards** with comprehensive panels
- **Telegram alerting** for critical issues
- **Custom alert rules** and thresholds


### ✅ Section 5: Rate Limiting \& Health Checks

- **Rate limiting** on critical endpoints
- **Comprehensive health checks** for all services
- **HTTP status codes** for different health states
- **Prometheus integration** for health metrics


## 🛠️ Prerequisites

- **PHP 8.4+**
- **Laravel 12**
- **Node.js 18+**
- **Docker \& Docker Compose**
- **Git**


## 📥 Installation Guide

### Step 1: Clone the Repository

```bash
https://github.com/davidchemwetich/realtime-monitoing-system
cd laravel-realtime-monitoring
```


### Step 2: Environment Setup

```bash
# Copy environment configuration
cp .env.example .env

# Install PHP dependencies
composer install

# Install Node.js dependencies
npm install

# Generate Laravel application key
php artisan key:generate
```


### Step 3: Configure Environment Variables

Update your `.env` file with the following configuration:

```env
# Application Settings
APP_NAME="Laravel Real-time Monitor"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

# Database Configuration
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=sail
DB_PASSWORD=password

# Queue & Cache Settings
QUEUE_CONNECTION=redis
CACHE_STORE=database

# Broadcasting Configuration
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=682265
REVERB_APP_KEY=jnjruzvlhp98079lbxuh
REVERB_APP_SECRET=ijr6qniw2isfygbgqqpc
REVERB_HOST="localhost"
REVERB_PORT=8080
REVERB_SCHEME=http

# Laravel Pulse Configuration
PULSE_ENABLED=true
PULSE_STORAGE_DRIVER=database
PULSE_CACHE_INTERACTIONS_ENABLED=true
PULSE_SLOW_QUERIES_THRESHOLD=500
PULSE_SLOW_REQUESTS_THRESHOLD=1000

# Horizon Configuration
HORIZON_PATH=horizon

# Rate Limiting
RATE_LIMIT_CHAT_MESSAGES=30
RATE_LIMIT_HEALTH_CHECKS=60
```


### Step 4: Start Docker Services

```bash
# Start all Docker containers
./vendor/bin/sail up -d

# Verify all containers are running
./vendor/bin/sail ps
```

**Expected Output:**

```
Name                        Command               State           Ports
--------------------------------------------------------------------------------
laravel.test              start-container          Up    0.0.0.0:80->80/tcp
mysql                      /entrypoint.sh mysqld    Up    0.0.0.0:3306->3306/tcp
redis                      docker-entrypoint.s...   Up    0.0.0.0:6379->6379/tcp
prometheus                 /bin/prometheus --co...   Up    0.0.0.0:9090->9090/tcp
grafana                    /run.sh                  Up    0.0.0.0:3000->3000/tcp
```


### Step 5: Database Setup

```bash
# Run database migrations
sail artisan migrate

# Seed database with test users (optional)
sail artisan db:seed
```


### Step 6: Build Frontend Assets

```bash
# For development
sail npm run dev

# For production
sail npm run build
```


## 🔧 Service Configuration

### Configure Required Directory Structure

```bash
# Create Prometheus configuration directories
mkdir -p docker/prometheus
mkdir -p docker/grafana/provisioning/datasources
mkdir -p docker/grafana/provisioning/dashboards
mkdir -p docker/grafana/provisioning/notifiers
mkdir -p docker/grafana/dashboards
mkdir -p docker/alertmanager
```


### Prometheus Configuration

Create `docker/prometheus/prometheus.yml`:

```yaml
global:
  scrape_interval: 15s
  evaluation_interval: 15s

rule_files:
  - "alert_rules.yml"

alerting:
  alertmanagers:
    - static_configs:
        - targets:
          - alertmanager:9093

scrape_configs:
  - job_name: 'laravel-app'
    static_configs:
      - targets: ['laravel.test:80']
    metrics_path: '/metrics'
    scrape_interval: 30s
    scrape_timeout: 10s
```


### Grafana Datasource Configuration

Create `docker/grafana/provisioning/datasources/prometheus.yml`:

```yaml
apiVersion: 1

datasources:
  - name: Prometheus
    type: prometheus
    access: proxy
    url: http://prometheus:9090
    isDefault: true
    editable: false
```


## 🚦 Starting the System

### Step 1: Start Core Services

```bash
# Start all Docker services
sail up -d

# Start Horizon queue workers
sail artisan horizon

# Start Reverb WebSocket server
sail artisan reverb:start --host=0.0.0.0 --port=8080
```


### Step 2: Verify Services are Running

```bash
# Check Docker containers status
sail ps

# Check application health
curl http://localhost/health

# Check metrics endpoint
curl http://localhost/metrics
```


## 🌐 Service URLs \& Access

| Service | URL | Credentials | Purpose |
| :-- | :-- | :-- | :-- |
| **Laravel App** | http://localhost | Register/Login | Main application |
| **Chat System** | http://localhost/chat | Authenticated users | Real-time chat |
| **Horizon Dashboard** | http://localhost/horizon | Admin access | Queue monitoring |
| **Laravel Pulse** | http://localhost/pulse | Authenticated users | Performance metrics |
| **Prometheus** | http://localhost:9090 | No auth | Metrics collection |
| **Grafana** | http://localhost:3000 | admin/admin123 | Dashboards \& alerts |
| **Health Check** | http://localhost/health | Public | System health |
| **Metrics Endpoint** | http://localhost/metrics | Public | Prometheus metrics |

## 🧪 Testing Guide

### 1. Real-time Chat Testing

#### Test User Registration and Login

```bash
# Register test users
curl -X POST http://localhost/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test User 1",
    "email": "user1@example.com",
    "password": "password",
    "password_confirmation": "password"
  }'
```


#### Test Chat Functionality

1. **Open multiple browser windows** to `http://localhost/chat`
2. **Login with different test users**
3. **Send messages** and verify real-time delivery
4. **Check presence indicators** showing active users

**Expected Result:**

- Messages appear instantly in all connected browsers
- User presence updates in real-time
- Message history is maintained


### 2. Rate Limiting Testing

#### Test `/notify` Endpoint Rate Limiting

```bash
# Test rate limiting (should get 429 after 30 requests)
for i in {1..35}; do
  echo "Request $i:"
  curl -X POST http://localhost/notify \
    -H "Content-Type: application/json" \
    -H "Cookie: laravel_session=YOUR_SESSION_COOKIE" \
    -d '{"message":"Test message '$i'"}' \
    -w "HTTP Status: %{http_code}\n"
  sleep 0.5
done
```

**Expected Behavior:**

- First 30 requests: `200 OK`
- Requests 31+: `429 Too Many Requests`
- Rate limit reset after 60 seconds


#### Test Health Check Rate Limiting

```bash
# Test health check rate limiting
for i in {1..65}; do
  curl -s http://localhost/health -w "Status: %{http_code}\n" | tail -1
done
```


### 3. Health Check Testing

#### Comprehensive Health Check

```bash
# Test health endpoint
curl -s http://localhost/health | jq '.'

# Expected healthy response
{
  "status": "healthy",
  "timestamp": "2025-01-21T15:30:00.000Z",
  "checks": {
    "database": {
      "status": "healthy",
      "message": "Database connection successful",
      "response_time_ms": 12.5
    },
    "cache": {
      "status": "healthy", 
      "message": "Cache system functional",
      "response_time_ms": 8.2
    },
    "queue": {
      "status": "healthy",
      "message": "Queue system accessible"
    }
  },
  "processing_time_ms": 45.8
}
```


#### Test Health Check Command

```bash
# Console health check
sail artisan health:check

# JSON output
sail artisan health:check --json

# Exit code testing
sail artisan health:check --exit-code
echo "Exit code: $?"
```


### 4. Queue System Testing

#### Generate Queue Jobs

```bash
# Generate test messages to create queue jobs
for i in {1..50}; do
  curl -X POST http://localhost/notify \
    -H "Content-Type: application/json" \
    -H "Cookie: laravel_session=YOUR_SESSION" \
    -d '{"message":"Queue test message '$i'"}' &
done
```


#### Monitor Queue Processing

1. **Access Horizon Dashboard**: http://localhost/horizon
2. **Check Active Jobs**: Monitor job processing in real-time
3. **View Failed Jobs**: Check retry mechanisms
4. **Monitor Throughput**: Observe jobs per minute

### 5. Prometheus Metrics Testing

#### Verify Metrics Endpoint

```bash
# Check if metrics are exposed
curl -s http://localhost/metrics | head -20

# Expected output:
# HELP laravel_app_health_status Overall application health
# TYPE laravel_app_health_status gauge
laravel_app_health_status 1

# HELP laravel_queue_size Number of jobs in queue  
# TYPE laravel_queue_size gauge
laravel_queue_size{queue="default"} 0
laravel_queue_size{queue="broadcasts"} 0
```


#### Test Specific Metrics

```bash
# Health metrics
curl -s http://localhost/metrics | grep -E "(health|database|cache)"

# Queue metrics  
curl -s http://localhost/metrics | grep -E "(queue|horizon)"

# Performance metrics
curl -s http://localhost/metrics | grep -E "(response_time|memory|cache_effectiveness)"
```


### 6. Grafana Dashboard Testing

#### Access Grafana

1. **Navigate to**: http://localhost:3000
2. **Login**: admin/admin123
3. **Check Data Source**: Configuration → Data Sources → Prometheus
4. **Import Dashboard**: Use provided JSON configuration

#### Verify Dashboard Panels

- ✅ **Requests per minute** showing traffic
- ✅ **Queue lengths** displaying job counts
- ✅ **Response times** tracking performance
- ✅ **Memory usage** monitoring resources
- ✅ **Cache effectiveness** showing hit rates
- ✅ **Error rates** tracking exceptions


### 7. Laravel Pulse Testing

#### Access Pulse Dashboard

1. **Navigate to**: http://localhost/pulse
2. **Login** with authenticated user
3. **Verify Metrics**:
    - Request throughput
    - Slow queries
    - Queue job processing
    - Cache performance
    - Exception tracking

#### Generate Test Data

```bash
# Generate slow queries
curl "http://localhost/slow-query-test"

# Generate cache misses
curl "http://localhost/cache-test"

# Generate exceptions (controlled)
curl "http://localhost/exception-test"
```


### 8. Load Testing

#### Comprehensive Load Test

```bash
#!/bin/bash
# load_test.sh

echo "🚀 Starting comprehensive load test..."

# Generate concurrent chat messages
for i in {1..100}; do
  curl -X POST http://localhost/notify \
    -H "Content-Type: application/json" \
    -H "Cookie: laravel_session=YOUR_SESSION" \
    -d '{"message":"Load test message '$i'"}' &
  
  if [ $((i % 10)) -eq 0 ]; then
    echo "Sent $i messages"
    sleep 1
  fi
done

echo "✅ Load test completed. Check metrics in Grafana."
```


#### Monitor During Load Test

```bash
# Watch system metrics in real-time
watch -n 2 'curl -s http://localhost/health | jq ".processing_time_ms"'

# Monitor queue processing
watch -n 5 'curl -s http://localhost/metrics | grep laravel_queue_size'

# Track memory usage
watch -n 3 'curl -s http://localhost/metrics | grep memory_usage'
```


## 📊 Performance Benchmarks

### Expected Performance Metrics

| Metric | Target | Threshold |
| :-- | :-- | :-- |
| **Message Throughput** | 1000+ msg/min | 500+ msg/min |
| **Response Time** | <200ms avg | <500ms avg |
| **Queue Processing** | <5s per job | <10s per job |
| **Memory Usage** | <128MB baseline | <256MB max |
| **Cache Hit Rate** | >80% | >70% |
| **Database Response** | <50ms | <100ms |

### Load Test Results Sample

```
📊 Load Test Results (100 concurrent users)
├── Total Requests: 10,000
├── Average Response Time: 185ms
├── 95th Percentile: 450ms
├── Error Rate: 0.02%
├── Queue Jobs Processed: 9,998/10,000
├── Memory Peak: 245MB
├── Cache Hit Rate: 87%
└── System Health: ✅ Stable
```


## 🔧 Configuration Reference

### Rate Limiting Thresholds

| Endpoint | Rate Limit | Burst Limit | Rationale |
| :-- | :-- | :-- | :-- |
| **Chat Messages** | 30/min | 10/10sec | Prevents spam while allowing natural conversation |
| **Health Checks** | 60/min | - | Allows monitoring tools while preventing abuse |
| **General API** | 100/min | - | Standard API protection |

### Alert Thresholds Documentation

| **Alert** | **Warning** | **Critical** | **Rationale** |
| :-- | :-- | :-- | :-- |
| **Queue Backlog** | >100 jobs | >500 jobs | Based on 3 workers × 30 jobs/min processing capacity |
| **Response Time** | >10 slow/5min | >20 slow/5min | >2% of requests being slow indicates performance degradation |
| **Memory Usage** | >128MB | >256MB | Laravel typically uses 64-128MB; 256MB allows headroom |
| **Error Rate** | >5 exceptions/5min | >10 exceptions/5min | Healthy applications have <0.1% error rate |
| **Cache Hit Rate** | <70% | <50% | Good cache implementation should achieve 80%+ hit rate |
| **Failed Jobs** | >10 total | >25 total | Failed jobs indicate logic or infrastructure issues |
| **DB Connections** | - | <1 active | Critical for application functionality |

## 🚨 Troubleshooting Guide

### Common Issues and Solutions

#### 1. WebSocket Connection Failed

```bash
# Check Reverb server status
sail artisan reverb:start --host=0.0.0.0 --port=8080

# Verify port accessibility
curl http://localhost:8080

# Check logs
sail logs laravel.test -f
```


#### 2. Queue Jobs Not Processing

```bash
# Check Horizon status
sail artisan horizon:status

# Restart Horizon
sail artisan horizon:terminate
sail artisan horizon

# Check Redis connection
sail artisan tinker
>>> \Illuminate\Support\Facades\Redis::ping()
```


#### 3. Prometheus Metrics Missing

```bash
# Verify metrics endpoint
curl http://localhost/metrics

# Check route registration
sail artisan route:list | grep metrics

# Verify collector registration
sail logs laravel.test | grep -i prometheus
```


#### 4. Health Check Failures

```bash
# Debug individual services
sail artisan health:check --json

# Check service dependencies
sail ps

# Test database connection
sail artisan tinker
>>> DB::select('SELECT 1')
```


#### 5. Grafana Dashboard Issues

```bash
# Check Prometheus data source
curl -u admin:admin123 http://localhost:3000/api/datasources

# Test Prometheus connectivity
curl http://localhost:9090/api/v1/targets

# Check Grafana logs
docker logs grafana -f
```


### Debug Commands Reference

```bash
# System Status
sail ps                              # Check all container status
sail logs laravel.test -f           # Application logs
sail logs horizon -f                # Horizon queue logs
sail logs prometheus -f             # Prometheus logs
sail logs grafana -f                # Grafana logs

# Application Debug
sail artisan health:check           # Health status
sail artisan horizon:status         # Queue worker status
sail artisan route:list             # Available routes
sail artisan queue:monitor          # Queue monitoring

# Performance Testing
curl http://localhost/metrics       # Check exposed metrics
curl http://localhost/health        # Health endpoint test
ab -n 100 -c 10 http://localhost/  # Apache Bench load test
```


## 📈 Monitoring Best Practices

### Daily Monitoring Checklist

- [ ] **System Health**: All services returning healthy status
- [ ] **Queue Processing**: No significant backlogs (< 100 jobs)
- [ ] **Response Times**: 95th percentile < 500ms
- [ ] **Error Rates**: < 0.1% exception rate
- [ ] **Memory Usage**: Stable with no upward trend
- [ ] **Cache Performance**: Hit rate > 80%
- [ ] **Database Performance**: Query response times < 100ms


### Alert Response Procedures

#### Critical Alerts (Immediate Response)

1. **Database Connection Issues**: Check MySQL container, connection pooling
2. **High Error Rate**: Review application logs, check recent deployments
3. **Critical Memory Usage**: Investigate memory leaks, restart if necessary

#### Warning Alerts (Monitor/Plan Response)

1. **High Queue Backlog**: Scale workers, investigate job failures
2. **Slow Response Times**: Profile slow endpoints, optimize queries
3. **Low Cache Effectiveness**: Review cache strategies, check TTL settings

## 🔒 Security Considerations

### Authentication \& Authorization

```php
// Horizon Dashboard Access Control
Gate::define('viewHorizon', function ($user = null) {
    return $user && in_array($user->email, [
        'admin@yourdomain.com',
    ]);
});

// Pulse Dashboard Access Control  
Gate::define('viewPulse', function ($user = null) {
    return $user !== null; // Authenticated users only
});
```


### Rate Limiting Security

- **Prevent Abuse**: Chat endpoint limited to 30 messages/minute
- **API Protection**: General API rate limiting at 100 requests/minute
- **Health Check Protection**: 60 requests/minute to prevent abuse
- **IP-based Limiting**: Fallback to IP-based limiting for unauthenticated users


### Metrics Endpoint Security

```php
// Optional: Protect metrics endpoint in production
Route::middleware('auth')->get('/metrics', function () {
    // Metrics endpoint logic
});
```


## 🚀 Deployment Guide

### Production Checklist

- [ ] **Environment Variables**: Production values configured
- [ ] **SSL Certificates**: HTTPS enabled for all services
- [ ] **Database Migrations**: All migrations run successfully
- [ ] **Queue Workers**: Configured with Supervisor for reliability
- [ ] **Prometheus Scraping**: Configured with appropriate intervals
- [ ] **Grafana Dashboards**: Imported and configured
- [ ] **Alert Rules**: Telegram bot configured for notifications
- [ ] **Rate Limiting**: Production thresholds set
- [ ] **Health Checks**: Configured for load balancer integration
- [ ] **Log Rotation**: Configured to prevent disk space issues
- [ ] **Backup Strategy**: Database and configuration backups
- [ ] **Monitoring**: All metrics flowing to dashboards


### Production Environment Variables

```env
# Production Configuration
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Database (Production values)
DB_HOST=your-production-db-host
DB_PASSWORD=secure-production-password

# Redis (Production values)  
REDIS_HOST=your-production-redis-host
REDIS_PASSWORD=secure-redis-password

# Broadcasting (Production values)
REVERB_HOST=yourdomain.com
REVERB_SCHEME=https

# Monitoring Thresholds (Production optimized)
PULSE_SLOW_QUERIES_THRESHOLD=200
PULSE_SLOW_REQUESTS_THRESHOLD=500
RATE_LIMIT_CHAT_MESSAGES=50
```


## 📚 API Reference

### Chat Endpoints

#### POST /notify

Send a chat message and broadcast to all connected users.

**Authentication:** Required
**Rate Limit:** 30 requests/minute, 10 requests/10 seconds

```bash
curl -X POST http://localhost/notify \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    "message": "Hello, world!"
  }'
```

**Response:**

```json
{
  "success": true,
  "message": "Message broadcast successfully.",
  "rate_limit_remaining": 29
}
```


### Health Check Endpoints

#### GET /health

Comprehensive system health status check.

**Rate Limit:** 60 requests/minute

```bash
curl http://localhost/health
```

**Response (Healthy):**

```json
{
  "status": "healthy",
  "timestamp": "2025-01-21T15:30:00.000Z",
  "checks": {
    "database": {
      "status": "healthy",
      "message": "Database connection successful",
      "response_time_ms": 12.5
    },
    "cache": {
      "status": "healthy",
      "message": "Cache system functional",
      "response_time_ms": 8.2
    },
    "queue": {
      "status": "healthy",
      "message": "Queue system accessible",
      "queue_sizes": {"default": 0, "broadcasts": 0}
    },
    "horizon": {
      "status": "healthy",
      "message": "Horizon workers active",
      "active_supervisors": 2
    }
  },
  "processing_time_ms": 45.8
}
```

**HTTP Status Codes:**

- `200 OK`: All systems healthy or degraded but functional
- `503 Service Unavailable`: Critical system failures


### Metrics Endpoints

#### GET /metrics

Prometheus metrics exposition endpoint.

```bash
curl http://localhost/metrics
```

**Sample Output:**

```
# HELP laravel_app_health_status Overall application health
# TYPE laravel_app_health_status gauge
laravel_app_health_status 1

# HELP laravel_queue_size Number of jobs in queue
# TYPE laravel_queue_size gauge
laravel_queue_size{queue="default"} 0
laravel_queue_size{queue="broadcasts"} 2

# HELP laravel_app_memory_usage_bytes Application memory usage
# TYPE laravel_app_memory_usage_bytes gauge
laravel_app_memory_usage_bytes 125829120
```


## 🤝 Contributing

### Development Setup

```bash
# Fork and clone the repository
git clone https://github.com/yourusername/laravel-realtime-monitoring.git

# Create feature branch
git checkout -b feature/amazing-feature

# Install development dependencies
composer install --dev
npm install

# Run tests
sail artisan test

# Code formatting
sail composer format
sail npm run lint

# Commit and push
git commit -m 'Add amazing feature'
git push origin feature/amazing-feature
```

### Performance Validation

```bash
# Load test with 50 concurrent requests
ab -n 50 -c 5 http://localhost/

# Monitor resource usage during test
docker stats --no-stream laravel.test mysql redis prometheus grafana
```

**Built with ❤️ using Laravel 12, Docker Sail, and the power of real-time monitoring**

<div style="text-align: center">⁂</div>
