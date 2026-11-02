<?php
/** Resolve only public phpSysInfo entry points and assets, never source/config files. */
function a2b_sysinfo_route($root, $path)
{
    if (!is_string($path) || !preg_match('~^/?[a-zA-Z0-9_./-]*$~D', $path)) {
        return false;
    }
    $path = ltrim($path, '/');
    if ($path === '') $path = 'index.php';
    if (preg_match('~(^|/)\.{1,2}(/|$)~', $path)) return false;
    $entryPoints = array('index.php', 'xml.php', 'js.php', 'templates/dynamic/css.php', 'language/language.php');
    // Also accept extensionless names for the public PHP entry points.
    if (in_array($path . '.php', $entryPoints, true)) $path .= '.php';
    $dynamic = in_array($path, $entryPoints, true);
    if (!$dynamic && !preg_match('~^(?:js|gfx|templates|language)/.+\.(?:css|js|png|gif|jpe?g|svg|ico|woff2?|ttf|eot|xml)$~iD', $path)) {
        return false;
    }
    $root = realpath($root);
    $file = $root === false ? false : realpath($root . '/' . $path);
    if ($file === false || strpos($file, $root . DIRECTORY_SEPARATOR) !== 0 || !is_file($file)) {
        return false;
    }
    return array('file' => $file, 'dynamic' => $dynamic);
}

function a2b_sysinfo_headers()
{
    // Upstream xml.php enables CORS; this embedded application is private.
    header_remove('Access-Control-Allow-Origin');
    header('Cache-Control: private, no-store');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
}

/** Map a package-relative URL to the authenticated controller; keep external URLs intact. */
function a2b_sysinfo_url($url, $source = 'index.php', $endpoint = 'A2B_phpsysinfo')
{
    if ($url === '' || preg_match('~^(?:[a-z][a-z0-9+.-]*:|/|\#)~i', $url)) return $url;
    $parts = parse_url($url);
    if ($parts === false) return $url;
    $relative = $parts['path'] ?? '';
    $path = $relative === '' ? $source : dirname($source) . '/' . $relative;
    $segments = array();
    foreach (explode('/', $path) as $segment) {
        if ($segment === '' || $segment === '.') continue;
        if ($segment === '..') {
            if (!$segments) return $url;
            array_pop($segments);
        } else {
            $segments[] = $segment;
        }
    }
    $path = implode('/', $segments);
    if (substr($relative, -1) === '/') $path .= '/';
    if (!preg_match('~^(?:(?:index|js|xml)\.php$|(?:gfx|templates|language|js|plugins)/)~', $path)) return $url;
    // The site's legacy THE_REQUEST rule also matches literal .php in queries.
    // Encode dots so that rule cannot truncate a resource name and its parameters.
    $result = $endpoint . '?sysinfo=' . str_replace(array('%2F', '.'), array('/', '%2E'), rawurlencode($path));
    if (isset($parts['query']) && $parts['query'] !== '') $result .= '&' . $parts['query'];
    if (isset($parts['fragment'])) $result .= '#' . $parts['fragment'];
    return $result;
}

function a2b_sysinfo_css_urls($text, $source, $endpoint)
{
    return preg_replace_callback('~url\(\s*([\'"]?)(.*?)\1\s*\)~is', function ($match) use ($source, $endpoint) {
        $url = a2b_sysinfo_url($match[2], $source, $endpoint);
        return $url === $match[2] ? $match[0] : 'url(' . $match[1] . $url . $match[1] . ')';
    }, $text);
}

/** Adapt responses, never the Composer package's files. XML/JSON and binary assets pass through. */
function a2b_sysinfo_rewrite($text, $mime, $source, $endpoint = 'A2B_phpsysinfo')
{
    if ($mime === 'text/css') return a2b_sysinfo_css_urls($text, $source, $endpoint);
    if (in_array($mime, array('application/javascript', 'application/x-javascript', 'text/javascript'), true)) {
        // Preserve concatenated paths such as 'gfx/images/' + distroIcon and
        // 'templates/bootstrap/' + template + '.css' in upstream rendering code.
        $text = preg_replace_callback('~(?<=[\'"])((?:\./)?(?:(?:index|xml|js)\.php|(?:language|templates|gfx|js|plugins)/)[^\'"\\\\\r\n]*)~',
            function ($match) use ($endpoint) { return a2b_sysinfo_url($match[1], 'index.php', $endpoint); }, $text);
        // The proxied language URL already has a query, including when lang is omitted.
        return str_replace('getLangUrl += "?plugin="', 'getLangUrl += "&plugin="', $text);
    }
    if ($mime !== 'text/html') return $text;
    $text = a2b_sysinfo_css_urls($text, $source, $endpoint);
    $text = preg_replace_callback('~(\b(?:src|href|action)\s*=\s*)([\'"])(.*?)\2~is',
        function ($match) use ($source, $endpoint) {
            $original = html_entity_decode($match[3], ENT_QUOTES, 'UTF-8');
            $url = a2b_sysinfo_url($original, $source, $endpoint);
            return $url === $original ? $match[0] : $match[1] . $match[2] . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . $match[2];
        }, $text);
    return preg_replace_callback('~(\bURL=)([^\'"<>]+)~i', function ($match) use ($source, $endpoint) {
        return $match[1] . htmlspecialchars(a2b_sysinfo_url(html_entity_decode($match[2], ENT_QUOTES, 'UTF-8'), $source, $endpoint), ENT_QUOTES, 'UTF-8');
    }, $text);
}

/** Called only after the controller has authenticated and checked both admin rights. */
function a2b_sysinfo_serve($root, $resource)
{
    foreach ($_GET as $value) {
        if (!is_string($value)) {
            http_response_code(400);
            return;
        }
    }
    if (isset($_GET['jsonp'])) {
        http_response_code(400);
        return;
    }
    $route = a2b_sysinfo_route($root, $resource);
    if (!$route) {
        http_response_code(404);
        return;
    }
    unset($_GET['sysinfo']);
    session_write_close();
    $source = substr($route['file'], strlen(realpath($root)) + 1);
    if ($route['dynamic']) {
        ob_start(function ($output) use ($source) {
            $mime = 'text/html';
            foreach (headers_list() as $header) {
                if (stripos($header, 'Content-Type:') === 0) $mime = strtolower(trim(explode(';', substr($header, 13))[0]));
            }
            return a2b_sysinfo_rewrite($output, $mime, $source);
        });
        chdir(dirname($route['file']));
        require $route['file'];
        ob_end_flush();
    } else {
        $types = array('css' => 'text/css', 'js' => 'application/javascript', 'xml' => 'application/xml',
            'svg' => 'image/svg+xml', 'png' => 'image/png', 'gif' => 'image/gif', 'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg', 'ico' => 'image/x-icon', 'woff' => 'font/woff', 'woff2' => 'font/woff2',
            'ttf' => 'font/ttf', 'eot' => 'application/vnd.ms-fontobject');
        $mime = $types[strtolower(pathinfo($route['file'], PATHINFO_EXTENSION))];
        header('Content-Type: ' . $mime);
        echo a2b_sysinfo_rewrite(file_get_contents($route['file']), $mime, $source);
    }
}
