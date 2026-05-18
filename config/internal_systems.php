<?php

return [
    'tokens' => json_decode(env('INTERNAL_BEARER_TOKENS', '{}'), true) ?: [],
];
