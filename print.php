<?php
ini_set('display_errors', 1);
$websiteURL = $_GET['url'];
$out = 'images/out-' . rand() . '.png';
//echo $websiteURL;

function exportToolPDF($src, $dst)
{

    try {
        //  global $src, $session;
        $r = exec('wkhtmltoimage ' . $src . ' ' . $dst);
        //        exec('/usr/local/bin/dump.sh '.$session);
        return 1;
    } catch (Exception $ex) {
        echo $ex->getMessage();
        return;
    }
}

exportToolPDF($websiteURL, $out);
if (file_exists($out)) {
    echo "<img src=\"$out\" />";
} else {
    //"https://www.facebook.com/public/Rasha-Atta";
    $api_response = file_get_contents("https://www.googleapis.com/pagespeedonline/v2/runPagespeed?url=$websiteURL&screenshot=true");
    //decode json data
    $result = json_decode($api_response, true);
    //screenshot data
    $screenshot = $result['screenshot']['data'];
    $screenshot = str_replace(array('_', '-'), array('/', '+'), $screenshot);
    //display screenshot image
    echo "<img src=\"data:image/jpeg;base64," . $screenshot . "\" />";
}
