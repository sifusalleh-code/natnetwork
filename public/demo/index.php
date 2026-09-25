<?php

// /demo ialah halaman galeri Laravel dan juga folder demo statik (public/demo/web/...).
// Server web akan menjalankan fail ini untuk /demo, jadi hantar permintaan ke Laravel.
$_SERVER['SCRIPT_FILENAME'] = dirname(__DIR__).'/index.php';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['PHP_SELF'] = '/index.php';

require dirname(__DIR__).'/index.php';
