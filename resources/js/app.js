/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

import * as game from './game.js';

require('./bootstrap');

window.Vue = require('vue').default;


/**
 * The following block of code may be used to automatically register your
 * Vue components. It will recursively scan this directory for the Vue
 * components and automatically register them with their "basename".
 *
 * Eg. ./components/ExampleComponent.vue -> <example-component></example-component>
 */

// const files = require.context('./', true, /\.vue$/i)
// files.keys().map(key => Vue.component(key.split('/').pop().split('.')[0], files(key).default))

Vue.component('chat-messages', require('./components/ChatMessages.vue'));
Vue.component('chat-form', require('./components/ChatForm.vue'));

/**
 * Next, we will create a fresh Vue application instance and attach it to
 * the page. Then, you may begin adding components to this application
 * or customize the JavaScript scaffolding to fit your unique needs.
 */

const app = new Vue({
    el: '#app_vuejs',
    data: {
        messages: []
    },

    /*
    created() {
        this.fetchMessages();
    },

    methods: {
        fetchMessages() {
            axios.get('/messages/').then(response => {
                this.messages = response.data;
            });
        },

        addMessage(message) {
            this.messages.push(message);

            axios.post('/messages', message).then(response => {
                console.log(response.data);
            });
        }
    }
    */
});

window.game = game;

document.addEventListener("DOMContentLoaded", function () {
    window.game.game.loadGame(mapWidth, mapHeight, mapTiles, gameId);

    let timer = 10000;

    if (document.getElementById("game_pusher_id").value !== '') {
        Echo.private('chat')
            .listen('MessageSent', (e) => {
                this.messages.push({
                    message: e.message.message,
                    user: e.user
                });
            });

        Echo.channel(`game-`+document.getElementById("game_id").value)
            .listen('.NextPlayer', (event) => {
                console.log("public");
                window.location.reload();
            });
        timer = 30000;
    }

    if (document.getElementById("game_reload").value !== '0') {
        window.setInterval(function () {
            window.location.reload();
        },timer)
    }
});


