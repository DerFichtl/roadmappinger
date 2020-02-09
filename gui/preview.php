<?php

ini_set('display_errors', true);
error_reporting(E_ALL);

try {
    require_once __DIR__.'/../Roadmappinger.php';

    $config = $_REQUEST['preview'];

    $roadmap = new Roadmap($config);
    $roadmap->draw();
    $roadmap->output();

    exit;

} catch(Exception $ex) {

}

if(isset($ex)) {
?>
    <!DOCTYPE html>
    <html><body><?php echo $ex->getMessage(); ?>
<?php } ?>


