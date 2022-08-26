export let chat = {
    initMessages: function (gameId) {
        this.loadMessages(gameId);
        if (document.getElementById("message")) {
            document.getElementById("message").focus();
        }
    },

    sendMessage: function (gameId) {
        $(".input-group").hide();
        $.ajax({
            type: "POST",
            url: "/message/"+gameId,
            data: {message: document.getElementById("message").value},
            success: function () {
                window.chat.chat.loadMessages(gameId);
            }
        });
        document.getElementById("message").value = '';
    },

    loadMessages: function (gameId) {
        if (document.getElementById("message")) {
            $.getJSON("/messages/" + gameId, function (data) {
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
