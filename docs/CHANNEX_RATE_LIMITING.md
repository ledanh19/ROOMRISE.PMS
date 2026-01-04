# Channex Rate Limiting Implementation

## Tổng quan

Hệ thống rate limiting cho Channex API đã được implement để tuân thủ các giới hạn của Channex:
- **Global limit**: 20 ARI requests per minute
- **Property limit**: 10 requests per minute per property
- **Payload size**: Tối đa 10MB per request

## Các thành phần chính

### 1. ChannexRateLimiter Service
- Quản lý rate limits cho global và property-specific
- Tự động chia nhỏ payload lớn
- Handle retry logic với exponential backoff

### 2. ChannexService Updates
- Tích hợp rate limiting vào tất cả API calls
- Handle rate limit responses từ Channex (cả body và header)
- Automatic retry với thời gian chờ từ server

### 3. Commands
- `php artisan channex:monitor-limits [property_id]` - Monitor rate limits
- `php artisan channex:test-rate-limits {property_id}` - Test rate limiting

## Cách hoạt động

### Rate Limit Detection
Hệ thống handle rate limits theo thứ tự ưu tiên:

1. **Response Body**: Kiểm tra `errors.details.retry_after` trong JSON response
2. **Response Header**: Kiểm tra `Retry-After` header
3. **Default**: Sử dụng 60 giây nếu không có thông tin

### Retry Logic
- Tối đa 3 lần retry cho mỗi request
- Chờ đúng thời gian `retry_after` + 1 giây buffer
- Reset local counters khi nhận rate limit response

### Payload Chunking
- Tự động chia payload lớn thành chunks 50 items
- Mỗi chunk được gửi riêng biệt với delay 2 giây
- Handle rate limits cho từng chunk

## Sử dụng

### Basic Usage
```php
$channexService = app(ChannexService::class);

// Update availability - tự động handle rate limits
$channexService->updateAvailability($availabilityData);

// Update restrictions - tự động handle rate limits
$channexService->updateRestrictions($restrictionData);
```

### Full Sync với Rate Limiting
```php
// Trong InventoryController
public function fullSync(Request $request, ChannexService $channexService)
{
    // Tự động handle rate limits cho 500 ngày sync
    $this->syncAvailabilityForAllRooms($property, $startDate, $endDate, $channexService);
    $this->syncRatesAndRestrictionsForAllRatePlans($property, $startDate, $endDate, $channexService);
}
```

### Monitor Rate Limits
```bash
# Check global và property limits
php artisan channex:monitor-limits

# Check specific property
php artisan channex:monitor-limits property-123

# Test rate limiting
php artisan channex:test-rate-limits property-123
```

### Job Queue cho Large Operations
```php
// Dispatch job cho full sync
ChannexFullSyncJob::dispatch($property->id);
```

## Logging

Hệ thống log chi tiết các events:

### Rate Limit Hit
```json
{
    "level": "warning",
    "message": "Channex rate limit hit",
    "context": {
        "endpoint": "restrictions",
        "property_id": "property-123",
        "retry_after": 20,
        "retry_after_source": "body",
        "response_headers": {
            "Retry-After": "20",
            "X-RateLimit-Limit": "10"
        }
    }
}
```

### Chunk Processing
```json
{
    "level": "info",
    "message": "Processing chunk 1 of 5",
    "context": {
        "chunk_size": 50,
        "property_id": "property-123"
    }
}
```

## Testing

### Run Tests
```bash
php artisan test tests/Feature/ChannexRateLimitTest.php
```

### Test Cases
- Rate limit handling với response body
- Rate limit handling với response header
- Payload chunking
- Payload size validation

## Configuration

### Rate Limits
```php
// Trong ChannexRateLimiter
private const GLOBAL_LIMIT = 20; // Total ARI requests per minute
private const PROPERTY_LIMIT = 10; // Per property per minute
private const MAX_PAYLOAD_SIZE = 10 * 1024 * 1024; // 10MB
```

### Retry Settings
```php
// Trong ChannexService
$maxRetries = 3;
$chunkSize = 50; // Items per chunk
$chunkDelay = 2; // Seconds between chunks
```

## Best Practices

1. **Monitor Usage**: Sử dụng commands để monitor rate limits
2. **Queue Large Operations**: Sử dụng job queue cho full sync
3. **Handle Errors**: Luôn handle exceptions từ rate limit errors
4. **Log Everything**: Monitor logs để debug issues
5. **Test Regularly**: Chạy tests để verify functionality

## Troubleshooting

### Common Issues

1. **Rate Limit Exceeded**: Check logs và wait for retry
2. **Large Payload**: System tự động chunk
3. **Network Errors**: Exponential backoff sẽ retry
4. **Timeout**: Increase timeout cho large operations

### Debug Commands
```bash
# Check current usage
php artisan channex:monitor-limits

# Test rate limiting
php artisan channex:test-rate-limits property-123

# Check logs
tail -f storage/logs/laravel.log | grep "Channex"
``` 
