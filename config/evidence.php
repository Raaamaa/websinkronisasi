<?php

declare(strict_types=1);

return [
    'max_file_size_mb' => (int) env('EVIDENCE_MAX_FILE_SIZE_MB', 20),
    'storage_warning_percent' => (int) env('STORAGE_WARNING_THRESHOLD_PERCENT', 80),
];

