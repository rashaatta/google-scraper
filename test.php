<?php
ini_set('display_errors', 1);
// include your composer dependencies
require_once 'google-api-php-client-2.2.1/vendor/autoload.php';

$client = new Google_Client();
$client->setApplicationName("Client_Library_Examples");
$client->setDeveloperKey("AIzaSyAlla4CLKMFSXfOXNtQz1IYHg6ApMFu4hg");

$service = new Google_Service_Books($client);
$optParams = array('filter' => 'free-ebooks');
$results = $service->volumes->listVolumes('Henry David Thoreau', $optParams);

foreach ($results as $item) {
  echo $item['volumeInfo']['title'], "<br /> \n";
}

?>
