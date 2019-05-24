<!doctype html>
<!-- saved from url=(0014)about:internet -->
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en"> 
    <!-- 
    Smart developers always View Source. 
    
    This application was built using Adobe Flex, an open source framework
    for building rich Internet applications that get delivered via the
    Flash Player or to desktops via Adobe AIR. 
    
    Learn more about Flex at http://flex.org 
    // -->
    <head>
        <title>PHPRay</title>
        <meta name="google" value="notranslate" />         
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <!-- Include CSS to eliminate any default margins/padding and set the height of the html element and 
             the body element to 100%, because Firefox, or any Gecko based browser, interprets percentage as 
             the percentage of the height of its parent container, which has to be set explicitly.  Fix for
             Firefox 3.6 focus border issues.  Initially, don't display flashContent div so it won't show 
             if JavaScript disabled.
        -->
        <style type="text/css" media="screen"> 
            html, body  { height:100%; }
            body { margin:0; padding:0; overflow:auto; text-align:center; 
                   background-color: #ffffff; }   
            object:focus { outline:none; }
            #flashContent { display:none; }

            .window {
                border: solid #7f7f7f 1px;
                background: #ffffff;
            }

            .window.center {
                position: fixed;
                max-width: 100%;
                max-height: 100%;
                width: 1024px;
                height: 758px;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                -webkit-transform: translate(-50%, -50%);
                -moz-transform: translate(-50%, -50%);
                -o-transform: translate(-50%, -50%);
                -ms-transform: translate(-50%, -50%);
            }

            .window.maximum {
                position: fixed;
                width: 100%;
                height: 100%;
                background: #ffffff;
                top: 0;
                left: 0;
            }

            .window.close {
                display: none;
            }

            .window * {
                font-size: 12px;
                margin: 0;
                padding: 0;
            }

            .window button {
                outline: none;
            }

            .window button:disabled {
                -webkit-filter: grayscale(100%); /* Safari 6.0 - 9.0 */
                filter: grayscale(100%);
            }

            .window>.layout {
                display: flex;
                flex-flow: column;
                height: 100%;
            }

            .window>.layout>.title {
                background: linear-gradient(to bottom, #eaeaea 0%, #d9d9d9 100%);;
                padding: 10px;
                border-bottom: solid #7f7f7f 1px;
                user-select: none;
                -moz-user-select: none;
                -ms-user-select: none;
                -webkit-user-select: none;
                flex: 0 1 auto;
            }

            .window>.layout>.tool {
                flex: 0 1 auto;
            }
            .window>.layout>.tool>button {
                width: 24px;
                height: 24px;
                background-color: #ffffff;
                background-size: 16px 16px;
                background-position: center;
                background-repeat: no-repeat;
                border: none;
            }

            .window>.layout>.tool>button:hover:enabled {
                border: solid 1px #7f7f7f;
                border-radius: 12px;
            }

            .window>.layout>.tool>button:active:enabled {
                background-color: #d9d9d9;
            }

            .window>.layout>.tool>.save {
                background-image: url("images/Save-icon.png");
            }
            .window>.layout>.tool>.reverse {
                background-image: url("images/undo-icon.png");
            }

            .window>.layout>.content {
                flex: 1 1 auto;
            }

            .window>.control {
                position: absolute;
                right: 10px;
                top: 10px;
            }

            .window>.control>button {
                width: 16px;
                height: 16px;
                border-radius: 10px;
            }

            .window>.control>.maximum {
                background-color: #718c00;
            }

            .window>.control>.close {
                background-color: #c82829;
            }

            #editor {
                width: 100%;
                height: 100%;
            }
        </style>
        <!-- Enable Browser History by replacing useBrowserHistory tokens with two hyphens -->
        <!-- BEGIN Browser History required section -->
        <link rel="stylesheet" type="text/css" href="history/history.css" />
        <script type="text/javascript" src="history/history.js"></script>
        <!-- END Browser History required section -->  
            
        <script type="text/javascript" src="swfobject.js"></script>
        <script src="scripts/ace/ace.js" type="text/javascript" charset="utf-8"></script>
        <script src="scripts/nanoajax.min.js" type="text/javascript" charset="utf-8"></script>
        <script type="text/javascript">
            // For version detection, set to min. required Flash Player version, or 0 (or 0.0.0), for no version detection. 
            var swfVersionStr = "11.1.0";
            // To use express install, set to playerProductInstall.swf, otherwise the empty string. 
            var xiSwfUrlStr = "playerProductInstall.swf";
            var flashvars = {};
            var params = {};
            params.quality = "high";
            params.bgcolor = "#ffffff";
            params.allowscriptaccess = "sameDomain";
            params.allowfullscreen = "true";
            var attributes = {};
            attributes.id = "Main";
            attributes.name = "Main";
            attributes.align = "middle";
            swfobject.embedSWF(
                "Main.swf?<?php echo filemtime('Main.swf'); ?>", "flashContent",
                "100%", "100%", 
                swfVersionStr, xiSwfUrlStr, 
                flashvars, params, attributes);
            // JavaScript enabled so display the flashContent div in case it is not replaced with a swf object.
            swfobject.createCSS("#flashContent", "display:block;text-align:left;");

            var editor = null;
            var focusInEditor = false;
            var originContent;
            var changeDetectTimer = 0;
            var fileName;
            var project;

            window.onload = function (ev) {
                initEditorWindow();

                editor = ace.edit("editor");
                editor.setTheme("ace/theme/monokai");
                editor.session.setMode("ace/mode/php");
                editor.on("focus", function(o){focusInEditor = true;});
                editor.on("blur", function(o){focusInEditor = false;});
                editor.on('change', function (o) {
                    if (changeDetectTimer !== 0) {
                        return;
                    }

                    changeDetectTimer = setTimeout(function() {
                        if (originContent === editor.getValue()) {
                            document.querySelector("#editorWindow>.layout>.tool>.save").setAttribute('disabled', 'disabled');
                        } else {
                            document.querySelector("#editorWindow>.layout>.tool>.save").removeAttribute('disabled');
                        }

                        changeDetectTimer = 0;
                    }, 1000);
                });

                document.onkeydown = function(ev){
                    var currKey = ev.keyCode||ev.which||ev.charCode;
                    if(currKey === 83 && (ev.ctrlKey||ev.metaKey)){
                        ev.preventDefault();
                        if (!focusInEditor) {
                            return;
                        }

                        save();
                    }
                }
            };

            function initEditorWindow() {
                var win = document.getElementById('editorWindow');
                function toggleMaximum() {
                    if (win.classList.contains('center')) {
                        win.classList.remove("center");
                        win.classList.add("maximum");
                    } else {
                        win.classList.remove("maximum");
                        win.classList.add("center");
                    }

                    editor.resize();
                }


                win.querySelector(".layout>.title").addEventListener('dblclick', toggleMaximum);
                win.querySelector(".control>.maximum").addEventListener('click', toggleMaximum);

                win.querySelector(".control>.close").addEventListener('click', function (evt) {
                    editor.blur();
                    win.classList.add('close');
                });

                win.querySelector(".layout>.tool>.save").addEventListener('click', save);
                win.querySelector(".layout>.tool>.reverse").addEventListener('click', reverse);
            }

            function edit(p, f, line) {
                nanoajax.ajax({url: '/', method: 'POST', body: encodeURIObject({
                        project: p,
                        fileName: f,
                        action: "main.fileGetContent"
                    })}, function (code, responseText, request) {
                    var response = JSON.parse(responseText);
                    if (response) {
                        if (response.error) {
                            alert(response.error);
                            return;
                        }

                        fileName = response.fileName;
                        project = p;
                        var title = fileName;
                        if (response.readonly) {
                            title = '(只读) ' + title;
                        }
                        document.querySelector("#editorWindow>.layout>.title").innerHTML = title;

                        editor.setReadOnly(response.readonly);
                        document.querySelector("#editorWindow>.layout>.tool>.save").setAttribute('disabled', 'disabled');

                        if (response.readonly || !response.debug) {
                            document.querySelector("#editorWindow>.layout>.tool>.reverse").setAttribute('disabled', 'disabled');
                        } else {
                            document.querySelector("#editorWindow>.layout>.tool>.reverse").removeAttribute('disabled');
                        }
                        document.getElementById("editorWindow").classList.remove('close');

                        originContent = response.content;
                        editor.session.setValue(response.content, typeof line === 'number' ? line : 0);
                    }
                });
            }

            function save() {
                if (editor.getValue() === originContent) {
                    return;
                }

                nanoajax.ajax(
                    {
                        url: '/',
                        method: 'POST',
                        body: encodeURIObject({
                                project:  project,
                                fileName: fileName,
                                action: 'main.filePutContent',
                                content: editor.getValue()
                            })
                    },
                    function (code, responseText, request) {
                        var response = JSON.parse(responseText);
                        if (response) {
                            if (response.error) {
                                alert(response.error);
                                return;
                            }

                            originContent = editor.getValue();
                            document.querySelector("#editorWindow>.layout>.tool>.save").setAttribute('disabled', 'disabled');
                            document.querySelector("#editorWindow>.layout>.tool>.reverse").removeAttribute('disabled');

                            document.getElementById('Main').refreshEdittingFile(true);
                        }
                    }
                );
            }

            function reverse() {
                nanoajax.ajax(
                    {
                        url: '/',
                        method: 'POST',
                        body: encodeURIObject({
                            project:  project,
                            fileName: fileName,
                            action: 'main.reverse'
                        })
                    },
                    function (code, responseText, request) {
                        var response = JSON.parse(responseText);
                        if (response) {
                            if (response.error) {
                                alert(response.error);
                                return;
                            }

                            document.querySelector("#editorWindow>.layout>.tool>.reverse").setAttribute('disabled', 'disabled');

                            document.getElementById('Main').refreshEdittingFile(false);
                        }
                    }
                );
            }

            function encodeURIObject(obj) {
                var params = [];
                for(var k in obj) {
                    if (!obj.hasOwnProperty(k)) {
                        continue;
                    }
                    params.push(encodeURIComponent(k) + "=" + encodeURIComponent(obj[k]));
                }

                return params.join('&');
            }

        </script>
    </head>
    <body>
        <!-- SWFObject's dynamic embed method replaces this alternative HTML content with Flash content when enough 
             JavaScript and Flash plug-in support is available. The div is initially hidden so that it doesn't show
             when JavaScript is disabled.
        -->
        <div id="flashContent">
            <p>
                To view this page ensure that Adobe Flash Player version 
                11.1.0 or greater is installed. 
            </p>
            <script type="text/javascript"> 
                var pageHost = ((document.location.protocol == "https:") ? "https://" : "http://"); 
                document.write("<a href='http://www.adobe.com/go/getflashplayer'><img src='" 
                                + pageHost + "www.adobe.com/images/shared/download_buttons/get_flash_player.gif' alt='Get Adobe Flash player' /></a>" ); 
            </script> 
        </div>
        
        <noscript>
            <object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" width="100%" height="100%" id="Main">
                <param name="movie" value="Main.swf" />
                <param name="quality" value="high" />
                <param name="bgcolor" value="#ffffff" />
                <param name="allowScriptAccess" value="sameDomain" />
                <param name="allowFullScreen" value="true" />
                <!--[if !IE]>-->
                <object type="application/x-shockwave-flash" data="Main.swf" width="100%" height="100%">
                    <param name="quality" value="high" />
                    <param name="bgcolor" value="#ffffff" />
                    <param name="allowScriptAccess" value="sameDomain" />
                    <param name="allowFullScreen" value="true" />
                <!--<![endif]-->
                <!--[if gte IE 6]>-->
                    <p> 
                        Either scripts and active content are not permitted to run or Adobe Flash Player version
                        11.1.0 or greater is not installed.
                    </p>
                <!--<![endif]-->
                    <a href="http://www.adobe.com/go/getflashplayer">
                        <img src="http://www.adobe.com/images/shared/download_buttons/get_flash_player.gif" alt="Get Adobe Flash Player" />
                    </a>
                <!--[if !IE]>-->
                </object>
                <!--<![endif]-->
            </object>
        </noscript>

        <div id="editorWindow" class="window center close">
            <div class="control">
                <button class="maximum"></button>
                <button class="close"></button>
            </div>
            <div class="layout">
                <h2 class="title">asdfasdfadsf</h2>
                <div class="tool">
                    <button class="save"></button>
                    <button class="reverse"></button>
                </div>
                <div class="content">
                    <div id="editor"></div>
                </div>
            </div>
        </div>
        <script>
        </script>
   </body>
</html>
