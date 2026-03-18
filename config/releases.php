<?php

$uploadLimitMegabytes = 1024;
$uploadLimitHuman = $uploadLimitMegabytes >= 1024 && $uploadLimitMegabytes % 1024 === 0
    ? (int) ($uploadLimitMegabytes / 1024) . ' GB'
    : $uploadLimitMegabytes . ' MB';
$uploadLimitPhp = $uploadLimitMegabytes . 'M';

return [
    'upload_limit_megabytes' => $uploadLimitMegabytes,
    'upload_limit_kilobytes' => $uploadLimitMegabytes * 1024,
    'upload_limit_human' => $uploadLimitHuman,
    'php_upload_max_filesize' => $uploadLimitPhp,
    'php_post_max_size' => $uploadLimitPhp,
    'nginx_client_max_body_size' => $uploadLimitPhp,
    'request_timeout_seconds' => 600,
];
