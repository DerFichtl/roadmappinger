<?php

ini_set('display_errors', true);
error_reporting(E_ALL);

require_once 'Roadmappinger.php';

if(isset($_GET['file'])) {
    $config = file_get_contents($_GET['file']);
    $roadmap = new Roadmap($config);
    $roadmap->draw();
    $roadmap->output();
}
