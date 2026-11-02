<?php
// Composer post-install/update hook. No changes to upstream PHP sources.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}
$lib = dirname(__DIR__, 2) . '/common/lib';
$package = $lib . '/vendor/phpsysinfo/phpsysinfo';
if (!is_file($lib . '/phpsysinfo.ini') || !is_file($package . '/read_config.php')) {
    fwrite(STDERR, "phpSysInfo package or project configuration is missing\n");
    exit(1);
}
$link = $package . '/phpsysinfo.ini';
$target = '../../../phpsysinfo.ini';
if (is_link($link) && readlink($link) === $target) {
    exit(0);
}
// Fail rather than overwrite an administrator's configuration.
if (file_exists($link) || is_link($link) || !symlink($target, $link)) {
    fwrite(STDERR, "Cannot create phpSysInfo configuration link: " . $link . "\n");
    exit(1);
}
echo "phpSysInfo uses the project configuration\n";
