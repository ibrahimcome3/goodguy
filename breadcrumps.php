<?php
    function breadcrumbs($separator = ' ', $home = 'Home') {
    $path = array_filter(explode('/', parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)));
    $base = (function() { return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')  || $_SERVER['SERVER_PORT'] == 443; } ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/';
    $breadcrumbs = array("<li class=\"breadcrumb-item\"><a href=\"$base\">$home</a></li>");
   // var_dump($path);
    $ar = array_keys($path);
    $last = end($ar);
    foreach ($path as $x => $crumb) {
        $title = ucwords(str_replace(array('.php', '_', 'lm'), array('', ' ', 'Shop'), $crumb));
        if ($x != $last) {
            $breadcrumbs[] = " <li class=\"breadcrumb-item\"><a href=\"$base$crumb\">$title</a></li>";
        } else {
            $breadcrumbs[] = " <li class=\"breadcrumb-item\"><a href=#\">$title</a></li>";   
        }
    }
    return implode($separator, $breadcrumbs);
}

   breadcrumbs();
?>