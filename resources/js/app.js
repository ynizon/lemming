/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

import * as chat from './chat.js';
import * as game from './lemming.js';

require('./bootstrap');
require('./sweetalert.min');

/**
 * The following block of code may be used to automatically register your
 * Vue components. It will recursively scan this directory for the Vue
 * components and automatically register them with their "basename".
 *
 * Eg. ./components/ExampleComponent.vue -> <example-component></example-component>
 */

// const files = require.context('./', true, /\.vue$/i)
// files.keys().map(key => Vue.component(key.split('/').pop().split('.')[0], files(key).default))

/**
 * Next, we will create a fresh Vue application instance and attach it to
 * the page. Then, you may begin adding components to this application
 * or customize the JavaScript scaffolding to fit your unique needs.
 */

window.game = game;
window.chat = chat;

document.addEventListener("DOMContentLoaded", function () {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    if (document.getElementById('message')) {
        document.querySelector('#message').addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                window.chat.chat.sendMessage(gameId);
            }
        });
    }

    if (document.getElementById('is_your_turn')) {
        window.game.game.loadGame(mapWidth, mapHeight, mapTiles, gameId);

        let timer = 15000;

        if (document.getElementById("game_pusher_id").value !== '') {
            Echo.channel(`chat-`+document.getElementById("game_id").value)
                .listen('.MessageSent', (e) => {
                    window.chat.chat.loadMessages(document.getElementById("game_id").value);
                });

            Echo.channel(`game-`+document.getElementById("game_id").value)
                .listen('.Reload', (event) => {
                    setTimeout(function () {
                        window.location.reload();
                    }, 2000);
                    if (document.getElementById("same").value !== "1") {
                        let audio = new Audio('/sounds/go.mp3');
                        audio.play();
                    }
                });

            //@TODO : I dont understand why it disconnect after 30 seconds
            window.Echo.connector.pusher.connection.bind('state_change', function (states) {
                if (states.current === 'disconnected') {
                    window.Echo.connector.pusher.connect();
                }
            });

            let timer = 120000;
        }

        if (document.getElementById("game_reload").value !== '0') {
            window.setTimeout(function () {
                if (document.getElementById('game_player')) {
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
            },timer)
        }

        if (document.getElementById("max_time")) {
            if (document.getElementById("same").value !== "1" && document.getElementById('is_your_turn').value === '1') {
                window.setInterval(function () {
                    let now = new Date();
                    let nowTime = now.getHours() * 3600 + now.getMinutes() * 60 + now.getSeconds() + now.getTimezoneOffset() * 60;
                    let max = document.getElementById('max_time').value.split(':');
                    let maxTime = parseInt(max[0]) * 3600 + parseInt(max[1]) * 60 + parseInt(max[2]);

                    if (nowTime >= maxTime && window.game.game.isContinueToPlay &&
                        document.getElementById('game_status').value === 'started') {
                        window.game.game.isContinueToPlay = false
                        window.game.game.timeOut();
                    }
                }, 1000)
            }
        }
    }
});


