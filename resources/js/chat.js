import {ajax} from './app';

export class Chat {
    constructor(gameId)
    {
        this.gameId = gameId;
        this.initMessages();
    }

    initMessages()
    {
        this.loadMessages(this.gameId);
        if (document.getElementById("message")) {
            document.getElementById("message").focus();
        }
    }

    sendMessage()
    {
        $(".input-group").hide();
        ajax.sendMessage(this.gameId);
        document.getElementById("message").value = '';
    }

    loadMessages()
    {
        if (document.getElementById("message")) {
            $.getJSON("/messages/" + this.gameId, function (data) {
                let ul = document.getElementById("messages");
                ul.innerHTML = '';
                $.each(data, function (key, val) {
                    let li = document.createElement("li");
                    li.appendChild(document.createTextNode(val.user.name + ": " + val.message));
                    ul.appendChild(li);
                });
                $(".input-group").show();
                document.getElementById("message").focus();
            });
        }
    }
}
