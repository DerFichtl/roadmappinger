<?php

    if(isset($_GET['save'])) {

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename=roadmap.rd');

        echo urldecode($_GET['save']);
        exit;
    }

    $defaultRoadmapData = file_get_contents('../data/roadmap.rd');
?>
<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="screen.css" />
</head>
<body>
    <div id="menu">
        <button id="save">Download</button>
        <button id="url">URL</button>
    </div>
    <div id="editor">
        <textarea id="roadmap"><?=$defaultRoadmapData?></textarea>
    </div>
    <div id="sep"></div>

    <iframe id="preview" name="preview" frameborder="0" border="0" src="preview.php?data="></iframe>
    <script type="text/javascript" src="app.js"></script>
</body>
</html>


