

function init() {

    var lastRoadmap = localStorage.getItem("rd_last_roadmap");
    var loadRoadmap = (location.search.indexOf("?load=") == 0) ? true : false;

    var roadmap = '';
    var textarea = document.getElementById('roadmap');

    if (loadRoadmap) {
        roadmap = decodeURIComponent(location.search.replace('?load=',''));
    } else if (lastRoadmap) {
        roadmap = lastRoadmap;
    }

    if(roadmap) {
        textarea.value = roadmap;
    }

    var timeout;
    textarea.addEventListener('input', function(){
        if(timeout){
            clearTimeout(timeout);
            timeout = null;
        }
        timeout = setTimeout(preview, 1000);
    });

    textarea.addEventListener('blur', function(evt){
        evt.preventDefault();

        if(textarea.selectionStart) {
            cursorPos = textarea.selectionStart;

            window.setTimeout(function(){
                textarea.focus();
                textarea.setSelectionRange(cursorPos, cursorPos);
            }, 90);
        }
    });



    preview();

    document.getElementById('save').addEventListener('click', function(){
        var data = textarea.value;
        var url = '?save=' + encodeURIComponent(data);

        window.location.assign(url);
    });

    document.getElementById('url').addEventListener('click', function(){
        var data = document.getElementById('roadmap').value;
        var url = 'http://' + document.location.host + document.location.pathname + '?load=' + encodeURIComponent(data);

        prompt('Roadmap URL:', url);
    });


    var resize = false;

    document.getElementById('sep').addEventListener('mousedown', function(){
        resize = true;
    });

    document.addEventListener('mouseup', function(){
        resize = false;
        document.getElementById('preview').style.display = 'block';
    });

    document.addEventListener('mousemove', function(evt){
        if(resize) {
            document.getElementById('preview').style.display = 'none';

            var windowWidth = window.innerWidth;
            var editorWidth = evt.clientX/(windowWidth/100);

            document.getElementById('editor').style.width = editorWidth+'%';
            document.getElementById('preview').style.width = (100-0.2-editorWidth)+'%';
        }
    });

}

function preview() {
    var textarea = document.getElementById('roadmap');
    var data = textarea.value;
    var cursorPos = 0;

    var iframeUrl = 'preview.php?data=' + encodeURIComponent(data);
    var elem = document.getElementById('preview');
    elem.contentWindow.location.href = iframeUrl;

    localStorage.setItem("rd_last_roadmap", data);
}


window.onload = init;







/*
var timeout;

window.onkeyup = function(){
    if(timeout){
        clearTimeout(timeout);
        timeout = null;
    }
    timeout = setTimeout(preview, 1000);
}
*/