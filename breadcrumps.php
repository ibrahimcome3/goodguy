<?php
function breadcrumbs($separator = ' ', $home = 'Home')
{
    $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
    $currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

    // Initialize session variable if it doesn't exist
    if (!isset($_SESSION['visited_paths'])) {
        $_SESSION['visited_paths'] = [];
    }

    // Add current path to the session if it's not already there
    if (!in_array($currentPath, $_SESSION['visited_paths'])) {
        $_SESSION['visited_paths'][] = $currentPath;
    }

    $visitedPaths = $_SESSION['visited_paths'];
    $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$basePath/";
    $breadcrumbs = ["<li class=\"breadcrumb-item\"><a href=\"$baseUrl\">$home</a></li>"];

    foreach ($visitedPaths as $i => $path) {
        $path = ltrim($path, '/');
        $parts = explode('/', $path);
        $title = '';
        foreach ($parts as $part) {
            if ($part !== 'goodguy') {
                $title .= ucwords(str_replace(array('.php', '_', 'lm'), array('', ' ', 'Shop'), $part)) . ' / ';
            }
        }
        $title = rtrim($title, ' / ');

        $url = $baseUrl . $path;
        if ($path === $currentPath) {
            $breadcrumbs[] = "<li class=\"breadcrumb-item active\">$title</li>";
        } else {
            $breadcrumbs[] = "<li class=\"breadcrumb-item\"><a href=\"$url\">$title</a></li>";
        }
    }


    return implode($separator, $breadcrumbs);
}


?>