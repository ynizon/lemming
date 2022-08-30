/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

require('./bootstrap');
require('./sweetalert.min');

import {Chat} from './chat.js';
import {Game} from './lemming.js';
import {Ajax} from './ajax.js';

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

export let ajax = new Ajax();
if (document.getElementById("game_id")) {
    let gameId = document.getElementById("game_id").value;
    window.game = new Game(mapWidth, mapHeight, mapTiles, gameId);
    game.loadGame(mapWidth, mapHeight, mapTiles);
    window.chat = new Chat(gameId);

    document.addEventListener("DOMContentLoaded", function () {
        if (document.getElementById('message')) {
            document.querySelector('#message').addEventListener('keypress', function (e) {
                if (e.key === 'Enter') {
                    window.chat.sendMessage(gameId);
                }
            });
        }

        if (document.getElementById('is_your_turn')) {
            let timer = 15000;

            if (document.getElementById("game_pusher_id").value !== '') {
                Echo.channel(`chat-`+document.getElementById("game_id").value)
                    .listen('.MessageSent', (e) => {
                        window.chat.loadMessages(document.getElementById("game_id").value);
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
                        ajax.reload(gameId);
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

                        if (nowTime >= maxTime && window.game.isContinueToPlay &&
                            document.getElementById('game_status').value === 'started') {
                            window.game.isContinueToPlay = false
                            window.game.timeOut();
                        }
                    }, 1000)
                }
            }
        }
    });
}
