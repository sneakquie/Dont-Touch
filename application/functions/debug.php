<?php defined('PATH') || die("Access denied");
function dump()
{
    foreach (func_get_args() as $target) {
        echo '<hr /><pre>';
        var_dump($target);
        echo '</pre><hr />';
    }
    exit;
}