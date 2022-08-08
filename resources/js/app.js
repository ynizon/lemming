/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

import * as game from './game.js';

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

document.addEventListener("DOMContentLoaded", function () {
    if (document.getElementById('message')) {
        document.querySelector('#message').addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                window.game.game.sendMessage(gameId);
            }
        });
    }

    if (document.getElementById('is_your_turn')) {
        window.game.game.loadGame(mapWidth, mapHeight, mapTiles, gameId);

        let timer = 10000;

        if (document.getElementById("game_pusher_id").value !== '') {
            Echo.channel(`chat-`+document.getElementById("game_id").value)
                .listen('.MessageSent', (e) => {
                    window.game.game.loadMessages(document.getElementById("game_id").value);
                });

            Echo.channel(`game-`+document.getElementById("game_id").value)
                .listen('.NextPlayer', (event) => {
                    window.location.reload();
                });
            timer = 30000;
        }

        if (document.getElementById("game_reload").value !== '0') {
            window.setInterval(function () {
                window.location.reload();
            },timer)
        }
    }
});


