<?php

namespace Knplabs\Snappy;

require_once('Knplabs/Snappy/Media.php');
require_once('Knplabs/Snappy/Image.php');
/* 'wkhtmltoimage' executable  is located in the current directory */
$snap = new Image('wkhtmltoimage');

/* Displays the bbc.com website index page screen-shot in the browser */
header("Content-Type: image/jpeg");
$out = 'images/out-' . rand() . '.png';

$url =$_GET['url'];

$snap->output($url);

//$output_dest = 'F';
//$snap->Output('https://github.com/KnpLabs/snappy/issues/104'.$out, $output_dest);



