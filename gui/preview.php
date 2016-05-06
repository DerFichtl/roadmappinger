<?php

ini_set('display_errors', true);
error_reporting(E_ALL);

try {
    require_once '../Roadmappinger.php';

    $config = $_REQUEST['data'];

    $roadmap = new Roadmap($config);
    $roadmap->draw();
    $roadmap->output();

    exit;

} catch(Exception $ex) {

}

if(isset($ex)) {
?>
<!DOCTYPE html>
<html>
    <head>
        <!-- <link rel="stylesheet" href="screen.css" />
        <script type="text/javascript" src="app.js"></script> -->
    </head>
    <body>
        <?php echo $ex->getMessage(); ?>
    </body>
</html>
<?php } ?>


