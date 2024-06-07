<?php
require '../adif.php';
$env = parse_ini_file('../.env');
$key = $env['KEY'];

$raw = file_get_contents('https://logbook.qrz.com/api?KEY=' . $key . '&ACTION=FETCH&OPTION=ALL');
$data = str_replace(['&lt;','&gt;'],['<','>'],$raw);

$adif = new adif(trim('<EOH>' . $data));
$parsed = $adif->parser();

print_r($parsed);
