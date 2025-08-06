
# Real-time Monitoring System  
## Section 2: Queue Management with Horizon

This section demonstrates advanced Laravel queue management using Laravel Horizon for monitoring and managing high-throughput message broadcasting in a real-time chat application.

---

## 🚀 Quick Start

### Prerequisites

- Docker & Docker Compose  
- Git

### Clone and Setup

```bash
# Clone the repository
git clone https://github.com/davidchemwetich/realtime-monitoing-system.git
cd realtime-monitoing-system

# Copy environment file
cp .env.example .env

# Start Laravel Sail
./vendor/bin/sail up -d

# Install dependencies
./vendor/bin/sail composer install

# Generate application key
./vendor/bin/sail artisan key:generate

# Run migrations
./vendor/bin/sail artisan migrate

# Install Horizon assets
./vendor/bin/sail artisan horizon:publish

# Seed database (optional - creates test users)
./vendor/bin/sail artisan db:seed
````

---

## 📋 Section 2 Features

### ✅ Laravel Horizon Configuration

* **Two Queue Workers**: `supervisor-1` (default) and `supervisor-broadcasts` (broadcasts)
* **High-throughput Settings**: Optimized concurrency, retry, and timeout configurations
* **Queue Balancing**: Auto-balancing strategy for optimal performance
* **Secure Dashboard**: Authentication-protected Horizon interface

### ✅ Queue Management

* **`ProcessBroadcastMessage` Job**: Handles message broadcasting asynchronously
* **Retry Mechanism**: 2 attempts with exponential backoff (5s, 10s)
* **Analytics Processing**: Message statistics and caching
* **Error Handling**: Comprehensive logging and failure management

---

## 🎯 Testing Queue Management

### 1. Start Queue Workers

```bash
# Start Horizon
./vendor/bin/sail artisan horizon

# Check status
./vendor/bin/sail artisan horizon:status
```

### 2. Access Horizon Dashboard

Visit: [http://localhost/horizon/dashboard](http://localhost/horizon/dashboard)
Monitor:

* **Workload**: Active supervisors and processes
* **Recent Jobs**: Completed job history
* **Failed Jobs**: Error tracking
* **Metrics**: Throughput and performance data

### 3. Test Message Broadcasting

* Access Chat: [http://localhost/chat](http://localhost/chat)
* Login with seeded users or register new ones
* Send messages and observe real-time processing in Horizon
* Open multiple browser tabs to simulate concurrent users

### 4. Manual Job Testing

```bash
# Open Laravel Tinker
./vendor/bin/sail artisan tinker

# Dispatch test job
$user = \App\Models\User::first();
\App\Jobs\ProcessBroadcastMessage::dispatch('Test message', $user);

# Check queue status
\Illuminate\Support\Facades\Redis::llen('queues:broadcasts');
```

### 5. Load Testing

```bash
# In tinker - dispatch multiple jobs
$user = \App\Models\User::first();
for ($i = 1; $i <= 50; $i++) {
    \App\Jobs\ProcessBroadcastMessage::dispatch("Load test #$i", $user);
}
```

---

## 🔧 Configuration Details

### Queue Workers Configuration

Located in `config/horizon.php`:

```php
'environments' => [
    'local' => [
        'supervisor-1' => [
            'queue' => ['default'],
            'maxProcesses' => 3,
            'tries' => 3,
            'timeout' => 60,
        ],
        'supervisor-broadcasts' => [
            'queue' => ['broadcasts'],
            'maxProcesses' => 2,
            'tries' => 2,
            'timeout' => 30,
        ],
    ],
],
```

### Job Configuration

The `ProcessBroadcastMessage` job includes:

* **Queue Assignment**: Automatically routed to `broadcasts` queue
* **Retry Logic**: 2 attempts with backoff delays
* **Analytics**: Message counting and caching
* **Error Logging**: Comprehensive failure tracking

---

## 📊 Performance Monitoring

### Key Metrics to Monitor

* **Jobs/Minute**: Message processing rate
* **Wait Time**: Queue delays (should be <30s for broadcasts)
* **Failed Jobs**: Error rate (should be minimal)
* **Memory Usage**: Worker resource consumption

### Expected Performance

* **Local Environment**: 2–3 concurrent processes per queue
* **Message Processing**: <5 seconds per job
* **Queue Wait Time**: <30 seconds under normal load
* **Retry Success Rate**: >90% on first retry

---

## 🛠 Troubleshooting

### Common Issues

#### Horizon Not Starting

```bash
# Clear configuration cache
./vendor/bin/sail artisan config:cache

# Restart Horizon
./vendor/bin/sail artisan horizon:terminate
./vendor/bin/sail artisan horizon
```

#### Jobs Not Processing

```bash
# Check Redis connection
./vendor/bin/sail redis redis-cli ping

# Verify queue configuration
./vendor/bin/sail artisan queue:work --once
```

#### Dashboard Access Issues

* Ensure you're logged in as an authenticated user
* Check `HorizonServiceProvider` gate configuration
* Verify route is accessible: [http://localhost/horizon](http://localhost/horizon)

### Debug Commands

```bash
# View application logs
./vendor/bin/sail logs

# Check failed jobs
./vendor/bin/sail artisan horizon:failed

# Clear failed jobs
./vendor/bin/sail artisan horizon:clear

# Monitor queue in real-time
./vendor/bin/sail artisan queue:listen
```

---

## 🔍 Verification Checklist

### ✅ Setup Verification

* Horizon dashboard accessible at `/horizon`
* Two supervisors visible in Workload tab
* No failed jobs initially
* Chat application accessible at `/chat`

### ✅ Functionality Tests

* Messages queue successfully via `/notify` endpoint
* Jobs appear in Recent Jobs tab
* Real-time broadcasting works between users
* Failed jobs retry automatically
* Analytics data stored in cache

### ✅ Performance Tests

* Multiple concurrent messages process smoothly
* Queue wait times remain under thresholds
* Memory usage stays within limits
* No job failures under normal load

---

## 📝 Implementation Notes

### Key Components

* `ProcessBroadcastMessage` Job: Core async message processor
* Horizon Configuration: Multi-queue setup with optimized settings
* `HorizonServiceProvider`: Security and access control
* Updated `ChatController`: Integration with queue system
* Redis Configuration: Queue backend with proper connection settings

### Architecture Benefits

* **Scalability**: Handle high message volumes without blocking UI
* **Reliability**: Retry mechanism ensures message delivery
* **Monitoring**: Real-time visibility into queue performance
* **Security**: Protected dashboard access
* **Performance**: Optimized queue processing with load balancing

---

## 🎓 Learning Objectives Achieved

* ✅ Laravel Horizon installation and configuration
* ✅ Multiple queue worker setup with different priorities
* ✅ Queueable job implementation with error handling
* ✅ High-throughput queue configuration
* ✅ Secure dashboard access and monitoring
* ✅ Real-time performance metrics and analytics

---

## 📚 Further Reading

* [Laravel Horizon Documentation](https://laravel.com/docs/horizon)
* [Queue Configuration](https://laravel.com/docs/queues)
* [Broadcasting Events](https://laravel.com/docs/broadcasting)
* [Redis Configuration](https://laravel.com/docs/redis)

---
