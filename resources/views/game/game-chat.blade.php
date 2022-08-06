@php
     use App\Models\Message;
     use App\Models\Game;
@endphp

<div class="panel panel-default chat">
    <h3>{{__('Chat')}}</h3>

    <ul id="messages">
    </ul>
    <div class="input-group">
        <input type="text" name="message" id="message" class="form-control input-sm"
               placeholder="{{__('Type your message here')}}...">

        <button class="btn btn-primary btn-sm" id="btn-chat" onclick="window.game.game.sendMessage({{$game->id}})">
            {{__("Send")}}
        </button>
    </div>

    <?php
    /*
    <div class="panel-body">
        <chat-messages :messages="messages"></chat-messages>
    </div>
    <div class="panel-footer">
        <chat-form
            v-on:messagesent="addMessage"
            :user="{{ Auth::user() }}"
        ></chat-form>
    </div>
    */
    ?>
</div>
