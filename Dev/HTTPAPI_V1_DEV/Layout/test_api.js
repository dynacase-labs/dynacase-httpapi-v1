/**
 * Created by charles on 28/10/14.
 */
!function ($) {
    $(document).ready(function () {
        var $currentURL = $("#request_url"),
            $currentMethod = $("#request_method"),
            $contentZone = $("#contentZone"),
            $listOfOptions = $("#listOfOptions"),
            content = CodeMirror.fromTextArea(document.getElementById("request_content"), {
                lineNumbers :       true,
                mode :              "application/json",
                gutters :           ["CodeMirror-lint-markers", "CodeMirror-linenumbers", "CodeMirror-foldgutter"],
                matchBrackets :     true,
                lint :              true,
                autoCloseBrackets : true,
                lineWrapping :      true,
                foldGutter :        true,
                extraKeys :         {
                    "F11" : function (cm) {
                        cm.setOption("fullScreen", !cm.getOption("fullScreen"));
                    },
                    "Esc" : function (cm) {
                        if (cm.getOption("fullScreen")) cm.setOption("fullScreen", false);
                    },
                    "Ctrl-R" : function(cm) {
                        var value = cm.getValue();
                        try {
                            value = JSON.parse(value);
                            value = JSON.stringify(value, null, "   ");
                            cm.setValue(value);
                        } catch (e) {
                            alert("The content JSON is not valid");
                        }
                    }
                }
            }), result = CodeMirror.fromTextArea(document.getElementById("request_result"), {
                lineNumbers :   true,
                mode :          "application/json",
                matchBrackets : true,
                readOnly :      true,
                lint :          true,
                lineWrapping :  true,
                foldGutter :    true,
                gutters :       ["CodeMirror-linenumbers", "CodeMirror-foldgutter"],
                extraKeys :     {
                    "F11" : function (cm) {
                        cm.setOption("fullScreen", !cm.getOption("fullScreen"));
                    },
                    "Esc" : function (cm) {
                        if (cm.getOption("fullScreen")) cm.setOption("fullScreen", false);
                    }
                }
            }), clone = function (source) {
                var clone = {}, prop;
                for (prop in source) {
                    if (source.hasOwnProperty(prop)) {
                        clone[prop] = source[prop];
                    }
                }
                return clone;
            }, writeResult = function (content) {
                result.setValue(content + "\n" + result.getValue());
            }, computeBaseURL = function () {
                return window.defaultValues.baseURL;
            }, currentRequest, setRequest = function (currentRequest) {
                try {
                    currentRequest = currentRequest || JSON.parse(decodeURIComponent(window.location.hash.slice(1)));
                    if (currentRequest.url) {
                        $currentURL.val(currentRequest.url);
                    }
                    if (currentRequest.data) {
                        content.setValue(currentRequest.data);
                    }
                    if (currentRequest.type) {
                        $currentMethod.val(currentRequest.type);
                        displayContentZone();
                    }
                } catch (e) {

                }
            }, displayContentZone = function () {
                var value = $currentMethod.val();
                if (value === "POST" || value === "PUT") {
                    $contentZone.show();
                } else {
                    $contentZone.hide();
                }
            };
        $currentURL.val(computeBaseURL());
        if (window.location.hash) {
            setRequest();
        }
        displayContentZone();
        $currentMethod.on("change", displayContentZone);
        $listOfOptions.on("change", function (event) {
            event.preventDefault();
            currentRequest = clone(window.examples[$listOfOptions.val()].params);
            currentRequest.url = computeBaseURL() + currentRequest.url;
            setRequest(currentRequest);
        });
        $("#request_form").on("submit", function (event) {
            event.preventDefault();
            writeResult("¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤");
            writeResult("Type " + $currentMethod.val());
            writeResult("URL " + $currentURL.val());
            writeResult("New request " + (new Date()));
            writeResult("¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤¤");
            var requestParams = {
                type :        $currentMethod.val(),
                dataType :    "json",
                contentType : 'application/json',
                url :         $currentURL.val()
            };
            if (requestParams.type === "POST" || requestParams.type === "PUT") {
                requestParams.data = content.getValue()
            }
            currentRequest = requestParams;
            window.location.hash = encodeURIComponent(JSON.stringify(requestParams));

            $.ajax(requestParams).done(function (data, textStatus, xhr) {
                writeResult(JSON.stringify(data, null, "    "));
                writeResult("********************************     Success " + xhr.status + " " + xhr.statusText + "    *****************************************");
            }).fail(function (xhr, textStatus) {
                var data = "";
                try {
                    data = JSON.parse(xhr.responseText);
                } catch (e) {
                    console.log(e);
                }
                writeResult("Full Response : " + JSON.stringify(xhr, null, "    "));
                writeResult("Data : " + JSON.stringify(data, null, "    "));
                writeResult("********************************     Fail " + xhr.status + " " + xhr.statusText + "    ********************************");
            });
        });
        $(window).on("hashchange", function () {
            var hash = window.location.hash.slice(1);
            if (JSON.stringify(currentRequest) !== hash) {
                setRequest();
            }
        }).on("resize", function() {
            var height = $(window).innerHeight() - $(".result-zone").position().top - 55;
            result.setSize(null, height);
        }).trigger("resize");
        $("#showDocumentation").on("click", function () {
            window.open(window.defaultValues.helpPage);
        });
        $("#sendButton").on("click", function() {
            $("#request_form").trigger("submit");
        });
        $("#cleanResult").on("click", function() {
           result.setValue("");
        });
    });
}(jQuery);