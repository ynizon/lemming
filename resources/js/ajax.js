export class Ajax {
    constructor()
    {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
    }

    sendMessage(gameId)
    {
        $.ajax({
            type: "POST",
            url: "/message/"+gameId,
            data: {message: document.getElementById("message").value},
            success: function () {
                window.chat.loadMessages(this.gameId);
            }
        });
    }

    reload(gameId)
    {
        $.ajax({
            type: "GET",
            url: "/reload/" + gameId + '/' + document.getElementById('game_player').value,
            data: {},
            success: function (data) {
                if (data !== '') {
                    window.location.reload();
                }
            }
        });
    }

    saveMap(newLandscape)
    {
        $.ajax({
            type: "POST",
            url: "/saveMap/" + document.getElementById("map_id").value,
            data: {
                "name": document.getElementById('name').value,
                "x": document.getElementById('changemap-x').value,
                "y": document.getElementById('changemap-y').value,
                "published": document.getElementById('published').value,
                "landscape": document.getElementById('changemap-landscape').value,
                "status": document.getElementById('changemap-status').value
            },
            success: function () {
                $("#hexa-" + newLandscape).click();
                if (document.getElementById('changemap-landscape').value === 'out') {
                    window.location.reload();
                }
            }
        });
    }

    timeout(gameId)
    {
        $.ajax({
            type: "GET",
            url: "/timeout/"+gameId,
            data: {},
            success: function (data) {
                document.getElementById('max_time').value = data;
                window.game.isContinueToPlay = true;
            }
        });
    }

    changeMap(gameId, mapId)
    {
        $.ajax({
            type: "GET",
            url: "/changeMap/"+gameId,
            data: {"map":mapId},
            success: function () {
                window.location.reload();
            }
        });
    }
}
