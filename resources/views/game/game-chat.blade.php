<div class="panel panel-default chat">
    <h3>{{__('Chat')}}</h3>

    <ul id="messages">
    </ul>
    <div class="input-group">
        <input type="text" name="message" id="message" class="form-control input-sm"
               placeholder="{{__('Type your message here')}}...">

        <button class="btn btn-primary btn-sm" id="btn-chat" onclick="window.chat.chat.sendMessage({{$game->id}})">
            {{__("Send")}}
        </button>
    </div>
</div>
