<?php

return [
    'server_name'         => env('MCP_SERVER_NAME') ?: env('APP_ENV', 'local'),
    'server_version'      => '1.0.0',
    'max_string_length'   => (int) env('MCP_MAX_STRING_LENGTH', 500),
    'tree_nodes_limit'    => (int) env('MCP_TREE_NODES_LIMIT', 300),
    'facets_limit'        => (int) env('MCP_FACETS_LIMIT', 50),
    'list_ttl_ms'         => 3_600_000,
];
