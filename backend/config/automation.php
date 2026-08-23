<?php

return [
    'max_items_per_run' => (int) env('AUTOMATION_MAX_ITEMS_PER_RUN', 100),
    'max_runtime_seconds' => (int) env('AUTOMATION_MAX_RUNTIME_SECONDS', 240),
    'max_api_requests' => (int) env('AUTOMATION_MAX_API_REQUESTS_PER_RUN', 50),
    'max_retries' => (int) env('AUTOMATION_MAX_RETRIES', 3),
    'chunk_size' => (int) env('AUTOMATION_CHUNK_SIZE', 25),
];
