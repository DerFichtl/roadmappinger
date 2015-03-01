<?php

ini_set('display_errors', true);
error_reporting(E_ALL);

if(isset($_REQUEST['preview'])) {

    try {
        require_once 'Roadmappinger.php';

        $config = $_REQUEST['data'];
        $roadmap = new Roadmap($config);
        $roadmap->draw();
        $roadmap->output();
    } catch(Exception $ex) {
        var_dump($ex);
    }

    exit;

} else {
    $defaultRoadmapData = file_get_contents('data/roadmap-2015.rd');
}

?>
<!DOCTYPE html>
<html>
<head>
    <style>
        html, body { width:100%; height:100%; margin:0; padding:0; }
        #roadmap { word-wrap:normal; white-space:pre; overflow:scroll; box-sizing:border-box; background:#eee; padding:20px; font-size:15px; margin:0; border:none; height:100%; width:40%; float:left; }
        #preview { box-sizing:border-box; background:#fff; padding:0; margin:0; border:none; height:100%; width:60%; float:right; }
    </style>
    <script type="text/javascript">

        function preview() {
            var data = document.getElementById('roadmap').value;
            console.log(data);
            parent.frames[0].document.location.href = '?preview=1&data=' + encodeURIComponent(data);
        }
        window.onload = preview;

        var timeout;
        window.onkeyup = function(){
            if(timeout){
                clearTimeout(timeout);
                timeout = null;
            }
            timeout = setTimeout(preview, 1000);
        }

    </script>
</head>
<body>
    <textarea id="roadmap"><?=$defaultRoadmapData?></textarea>
    <iframe frameborder="0" border="0" id="preview" name="preview" src="#"></iframe>
</body>
</html>


