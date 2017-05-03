<?php
@ini_set("max_execution_time", 300);


include 'vendor/autoload.php';
include 'inc/carddav.php';
include 'inc/NamiConnector.php';
include 'settings.php';
include 'inc/NamiToCardDav.php';

use Sabre\VObject;


echo '<pre>';

new Nami_To_Card_Dav( $settings_array );