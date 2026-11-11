<?php

declare(strict_types=1);

return [
    'max_file_size_mb' => (int) env('EVIDENCE_MAX_FILE_SIZE_MB', 50),
    'storage_warning_percent' => (int) env('STORAGE_WARNING_THRESHOLD_PERCENT', 80),
    'matching_suggest_threshold' => (float) env('MATCHING_SIMILARITY_THRESHOLD', 75.0),
    'matching_review_threshold' => (float) env('MATCHING_REVIEW_THRESHOLD', 60.0),
    'drive_api_key' => env('DRIVE_API_KEY', ''),
    'drive_sync_max_depth' => (int) env('DRIVE_SYNC_MAX_DEPTH', 5),
    'drive_sync_schedule_enabled' => filter_var(env('DRIVE_SYNC_SCHEDULE_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
];

