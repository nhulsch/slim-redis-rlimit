# Slim Redis Ratelimit
PSR-7 Ratelimiter

## Usage
```php
$app = new Slim\App();

$app->add(new \Slim\Middleware\RedisRatelimit('tcp://127.0.0.1:6379', 500, 300));
```

This will be called on every requests and checks if the number of requests (in this case 500) exceeds within 300
seconds.

Class will also check if the Cloudflare UserIP header is set and will use that for tracking.