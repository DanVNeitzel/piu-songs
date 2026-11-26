<?php
/** Router for PHP's local development server. */
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/');
if (str_contains($path, "\0") || preg_match('~(?:^|/)(?:\.|app(?:/|$)|tests(?:/|$)|files_config\.json$|zip_progress\.json$)~i', str_replace('\\', '/', $path))) {
    http_response_code(403);
    exit('Forbidden');
}
return false;
