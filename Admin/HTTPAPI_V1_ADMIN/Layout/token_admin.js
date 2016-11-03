// Token Access
$(document).ready(function ()
{
    "use strict";
    var $token = $('.token');
    var $helpButton;


    $token.on("token.info", function (event, data)
    {
        var $info = $(".token-info");
        var $ctxAdd = $info.find(".token-add-key");
        var emptyQuery=true;

        if (data.context) {
            for (var key in data.context) {
                if (data.context.hasOwnProperty(key)) {
                    if (data.context[key] !== undefined) {
                        $ctxAdd.trigger("click");
                        if (typeof data.context[key].methods === "object") {
                            $info.find(".context-param tbody input.token-param-method").last().val(data.context[key].methods.join(', '));
                        }
                        $info.find(".context-param tbody input.token-param-route").last().val(data.context[key].route);

                        if (data.context[key].query) {
                            var sQuery = [];
                            for (var kq in data.context[key].query) {
                                if (data.context[key].query.hasOwnProperty(kq)) {
                                    if (data.context[key].query[kq] !== undefined) {
                                        sQuery.push(kq + '=' + encodeURIComponent(data.context[key].query[kq]));
                                    }
                                }
                            }
                            if (sQuery.length > 0) {
                                emptyQuery=false;
                            }
                            $info.find(".context-param tbody input.token-param-query").last().val('?' + sQuery.join("&"));
                        }

                    }
                }
            }
            if (emptyQuery) {
                $info.find("th.token-query").addClass("token-query--empty");
            } else {
                $info.find("th.token-query").removeClass("token-query--empty");
            }
            if ($info.find(".context-param tbody tr").length === 0) {
                $info.find(".context-param thead").hide();
            } else {
                $info.find(".context-param thead").show();
            }
        }

    });



    $(".token-view-routes").button({
        "icon": "ui-icon-help"
    }).on("click", function ()
    {
        var $routeInfo = $(".token-routes");
        var url = "?app=HTTPAPI_V1_ADMIN&action=TOKEN_METHOD&method=routes";
        $helpButton = $(this);

        if ($routeInfo.length === 0) {
            $.getJSON(url).done(function (data)
            {
                var $routeInfo = $(".token-routes");
                var $ul;
                if ($routeInfo.length === 0) {
                    $routeInfo = $('<div><table><tbody></tbody></table></div>').addClass("token-routes");
                }

                if (data && data.info) {
                    $ul = $routeInfo.find("tbody");
                    $ul.html('');
                    for (var i = 0; i < data.info.length; i++) {
                        $ul.append($("<tr/>").data("index", i).append($("<td><a class='token-routes-choose'></a>")).append($("<td/>").addClass("token-routes-desc").text(data.info[i].description)).append($("<td/>").addClass("token-routes-url").text(data.info[i].canonicalURL)));
                    }

                    $routeInfo.dialog({
                        "width": "auto",
                        "height": $(window).height() - 100,
                        "title": data.message,
                        "modal": true,
                        "open": function (event, ui)
                        {

                        }
                    });
                    $routeInfo.find(".token-routes-choose").button({
                        "icon": "ui-icon-copy"
                    });
                    $routeInfo.on("click", ".token-routes-choose", function ()
                    {
                        var index = $(this).closest("tr").data("index");
                        $helpButton.closest("td").find("input").val(data.info[index].regExp);
                        $routeInfo.dialog("close");
                    });

                }

            }).fail(function (response)
            {
                var $div = $('<div/>').html(response.responseText);
                $div.find("link").remove();
                $div.dialog();
            });
        } else {
            $routeInfo.dialog("open");
        }

    });

});