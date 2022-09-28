import {Grid, grid} from './grid';
import Swal from 'sweetalert2';
import {ajax} from './app';

export class Game {
    constructor(mapWidth, mapHeight, mapTiles, gameId)
    {
        this.gameId = gameId;
        this.sound = false;
        this.hasTakenATile = false;
        this.currentCard =  null;
        this.currentLemming = null;
        this.currentTile = null;
        this.path = [];
        this.fullPath = [];
        this.isContinueToPlay = true;
        this.maxTilesPath = 0;
        this.landscapePath = null;
        this.placeMarkerLandscape = '';
        this.tilesLandscape = {water:0, earth:0, rock:0, forest:0, desert:0};
        this.isYourTurn = 0;
    }

    loadGame(width, height, map)
    {
        this.isYourTurn = parseInt(document.getElementById('is_your_turn').value);
        let deserializedGrid = JSON.parse(map);

        deserializedGrid.forEach((hexa) => {
            let coord = {x: hexa.x, y:hexa.y};
            grid.get(coord).landscape = hexa.landscape;
            grid.get(coord).picture = hexa.picture;
            grid.get(coord).finish = hexa.finish;
            grid.get(coord).start = hexa.start;
            grid.get(coord).text = hexa.text;
            grid.get(coord).draw.fill(grid.get(coord).picture) ;
        });

        this.initInfos();
        this.initButtons();
        this.initCards();
        this.initMap();
        this.initLemmings();
        this.InitStartAndFinish();
        this.initMouse();
        this.initLastMoves();
    }

    initInfos()
    {
        let showInfo = this.getCookieValue("info");
        if (showInfo === 'false') {
            $("#info").hide();
        }
    }

    initButtons()
    {
        $(".clicker").each(function () {
            $(this).on("click", function () {
                let audio = new Audio('/sounds/click.mp3');
                audio.play();
            });
        });
    }

    initCards()
    {
        $(".yourcard").each(function () {
            $(this).on("click", function () {
                window.game.cardClick($(this));
            });
        });
    }

    cardClick(card)
    {
        if (window.game.hasTakenATile) {
            window.game.popin(game.__("You can't, because you have already change the map"), "error");
        } else {
            if (window.game.isYourTurn && window.game.path.length === 0) {
                window.game.placeMarkerLandscape = '';
                let audio = new Audio('/sounds/card.mp3');
                audio.play();

                if (window.game.currentCard) {
                    window.game.resetCard();
                }
                window.game.currentCard = card;
                card.addClass("selected");
                let score = parseInt(card.attr('data-score'));
                let landscape = card.attr('data-landscape');
                let cardId = card.attr('data-cardid');

                window.game.landscapePath = landscape;

                $(".cards-deck").each(function () {
                    card.html(card.attr("data-origine"));
                });

                let min = parseInt($('#score-' + landscape).attr("data-min"));
                if (score <= min) {
                    let total = score + parseInt($('#score-' + landscape).attr("data-score"));
                    window.game.maxTilesPath = total;
                    $('#score-' + landscape).html(
                        $('#score-' + landscape).attr("data-origine") + ' + ' + score + ' = ' + total
                    );
                } else {
                    let total = score;
                    window.game.maxTilesPath = total;
                    $('#score-' + landscape).html(
                        score + ' = ' + total
                    );
                    if (window.game.tilesLandscape[landscape] === "0") {
                        landscape = 'meadow';
                    }
                    window.game.placeMarkerLandscape = landscape;
                    $("#tile-hover .hexagonemain").attr('class', 'hexagonemain').addClass("hex-"+landscape);
                    window.game.popinPlaceMarker(window.game.__("You should now replace a tile by a tile ") +
                        window.game.__(landscape) + ".", "warning");
                    let allHexa = document.querySelectorAll("polygon");
                    allHexa.forEach((hexa) => {
                        hexa.classList.add('cursor_map');
                    });
                }
                $('#card_id').val(cardId);
            }
        }
    }

    InitStartAndFinish()
    {
        window.setTimeout(function () {
            grid.forEach((hexa) => {
                if (hexa.start) {
                    hexa.text = document.getElementById("icon_start").value;
                    hexa.addMarker();
                    hexa.draw.fill("#999999") ;
                }
                if (hexa.finish) {
                    hexa.text = document.getElementById("icon_finish").value;
                    hexa.addMarker();
                    hexa.draw.fill("#999999") ;
                }
            });

            //Default Lemming is 1
            let start = false;
            if ($('#lemming1').length > 0) {
                if ($('#lemming1').attr("data-finish") === "0") {
                    $('#lemming1').click();
                    start = true;
                }
            }
            if (!start && $('#lemming2').length > 0) {
                if ($('#lemming2').attr("data-finish") === "0") {
                    $('#lemming2').click();
                }
            }
        }, 1000);
    }

    initMouse()
    {
        document.addEventListener('mousemove', e => {
            if (window.game.placeMarkerLandscape !== '') {
                $("#tile-hover").css({left:e.pageX+10, top:e.pageY-10});

                let hexmap = document.querySelector('#hexmap');
                let correctOffsetX = event.clientX-$('#hexmap').offset().left;
                let correctOffsetY = event.clientY-$('#hexmap').offset().top;

                if (hexmap.contains(event.target)) {
                    const hexCoordinates = Grid.pointToHex([correctOffsetX, correctOffsetY]);
                    const hex = grid.get(hexCoordinates);
                    if (hex) {
                        hex.draw
                            .stop(true, true)
                            .fill({ opacity: 0.5 })
                            .animate(600)
                            .fill({ opacity: 1 })
                    }
                }
            }
        });
    }

    initLastMoves()
    {
        window.game.seeLastMoves();
        window.setTimeout(function () {
            window.game.seeLastMoves();
        }, 3000);
    }

    initLemmings()
    {
        $(".lemming").each(function ( ) {
            if ($(this).attr('data-x') !== "-1" && $(this).attr('data-y') !== "-1") {
                let coord = {x: parseInt($(this).attr('data-x')), y: parseInt($(this).attr('data-y'))};
                let hex = grid.get(coord);
                hex.text = $(this).attr("data-content");
                let currentIcon = document.getElementById("current_icon").value;
                if (!hex.start || $(this).attr('data-content') === currentIcon) {
                    if (!hex.finish) {
                        hex.color = window.game.getColorLemming($(this));
                        hex.addMarker();
                    }
                }
            }
        });

        $("#lemming1").on("click", function () {
            window.game.lemmingClick(this.id);
        });

        $("#lemming2").on("click", function () {
            window.game.lemmingClick(this.id);
        });
    }

    lemmingClick(lemmingId)
    {
        if (window.game.isYourTurn) {
            if (window.game.sound) {
                let audio = new Audio('/sounds/lemming.mp3');
                audio.play();
            }
            window.game.sound = true;//No sound before user click on DOM

            if ($("#" + lemmingId).attr("data-finish") === "1") {
                window.game.popin(window.game.__("This lemming has already finished"), "error");
            } else {
                if (window.game.path.length > 0) {
                    window.game.popin(this.__("You can't move 2 lemmings"), "error");
                } else {
                    let allHexa = document.querySelectorAll("polygon.cursor");
                    allHexa.forEach((adjacentHexa) => {
                        adjacentHexa.classList.remove('cursor');
                    });
                    window.game.currentLemming = $("#" + lemmingId);
                    $(".lemming").removeClass("selected");
                    $("#" + lemmingId).addClass("selected");
                    document.getElementById('num_lemming').value = $("#" + lemmingId).attr("data-lemming");

                    $("polygon").removeClass("selected");
                    let adjacentsHexa = [];
                    this.currentTile = null;
                    if ($("#" + lemmingId).attr('data-x') !== "-1" && $("#" + lemmingId).attr('data-y') !== "-1") {
                        let coord = {
                            x: parseInt($("#" + lemmingId).attr('data-x')),
                            y: parseInt($("#" + lemmingId).attr('data-y'))
                        };
                        let hex = grid.get(coord);
                        document.getElementById(hex.draw.node.id).classList.add('selected');
                        window.game.currentTile = hex;
                        adjacentsHexa = window.game.getAdjacentHexa(hex);
                    } else {
                        adjacentsHexa = window.game.getStartHexa();
                    }

                    let icons = document.querySelectorAll("text");
                    icons.forEach((icon) => {
                        icon.classList.remove('cursor');
                    });
                    adjacentsHexa.forEach((adjacentHexa) => {
                        document.getElementById(adjacentHexa.draw.node.id).classList.add('cursor');
                        icons = document.querySelectorAll("text[class*='x-" + adjacentHexa.x + "_y-" + adjacentHexa.y + "']");
                        icons.forEach((icon) => {
                            icon.classList.add('cursor');
                        });
                    });
                }
            }
        }
    }

    initMap()
    {
        this.tilesLandscape.earth = $('#nb_earth').val();
        this.tilesLandscape.water = $('#nb_water').val();
        this.tilesLandscape.forest = $('#nb_forest').val();
        this.tilesLandscape.desert = $('#nb_desert').val();
        this.tilesLandscape.rock = $('#nb_rock').val();

        let gameIsStarted = parseInt(document.getElementById("is_started").value);
        let hexmap = document.querySelector('#hexmap');
        document.addEventListener('click', ({ offsetX, offsetY }) => {
            let correctOffsetX = offsetX;
            let correctOffsetY = offsetY;
            if (navigator.userAgent.toLowerCase().indexOf('firefox') > -1) {
                //Spec Bugs for FF: https://github.com/w3c/csswg-drafts/issues/1508
                correctOffsetX = event.clientX-$('#hexmap').offset().left;
                correctOffsetY = event.clientY-$('#hexmap').offset().top;
            }

            if (hexmap.contains(event.target) && gameIsStarted && this.isYourTurn) {
                const hexCoordinates = Grid.pointToHex([correctOffsetX, correctOffsetY]);
                const hex = grid.get(hexCoordinates);

                if (this.placeMarkerLandscape !== '') {
                    if ((document.getElementById('editor').value === '0') &&
                        (hex.start || hex.finish || hex.landscape === 'out')) {
                        this.popin(this.__("You can\'t put a tile on this area"), 'error');
                    } else {
                        let allHexa = document.querySelectorAll("polygon");
                        allHexa.forEach((hexa) => {
                            hexa.classList.remove('cursor_map');
                        });

                        $("#changemap-x").val(hex.x);
                        $("#changemap-y").val(hex.y);
                        $("#changemap-landscape").val(this.placeMarkerLandscape);
                        if (this.editorStartOrFinish === 'start' || this.editorStartOrFinish === 'finish') {
                            $("#changemap-status").val(this.editorStartOrFinish);
                            this.editorStartOrFinish = '';
                        }

                        grid.get(hex).landscape = this.placeMarkerLandscape;
                        grid.get(hex).draw.fill('/images/' + this.placeMarkerLandscape + '.png');

                        this.placeMarkerLandscape = '';
                        $("#tile-hover").hide();
                        this.hasTakenATile = true;
                        this.info('');

                        if (document.getElementById("editor")) {
                            if (document.getElementById("editor").value === "1") {
                                let newLandscape = document.getElementById('changemap-landscape').value
                                ajax.saveMap(newLandscape);
                            }
                        }
                    }
                } else {
                    if (!(document.getElementById('editor').value === "0")) {
                        return;
                    }
                    if (!(this.currentLemming)) {
                        this.popin(this.__("Select your lemming first"), "error");
                        return;
                    }
                    if (!(this.currentCard)) {
                        this.popin(this.__("Select a card before"), "error");
                        return;
                    }
                    if (!(this.fullPath.length < this.maxTilesPath)) {
                        this.popin(this.__("Maximum path exceeded"), "error");
                        return;
                    }
                    if (hex) {
                        if (hex.landscape === 'none' || hex.landscape === 'meadow' ||
                            hex.landscape === this.landscapePath ||
                            hex.start ||
                            hex.finish) {
                            var canMove = false;
                            if (!this.currentTile) {
                                if (hex.start) {
                                    canMove = true;
                                } else {
                                    this.info(this.__("This is not a start area"), 'error');
                                }
                            } else {
                                if (this.isAdjacentHexa(hex)) {
                                    canMove = true;
                                }
                            }
                            if (canMove) {
                                let direction = this.getDirection(this.currentTile, hex);
                                if (hex.text !== '' && !hex.finish) {
                                    if (this.fullPath.length >= (this.maxTilesPath - 1)) {
                                        this.popin(this.__("You don't have enough moves to push the other lemming(s)"), "error");
                                    } else {
                                        this.askPushLemming(this.__("Do you want push the other lemming ?"), "question", hex, direction);
                                    }
                                } else {
                                    this.updateLemmingPosition(hex, this.currentLemming);
                                }
                            } else {
                                if (this.currentTile) {
                                    this.popin(this.__("This tile is not accessible"), "error");
                                }
                            }
                        } else {
                            if (hex.landscape !== 'out') {
                                this.popin(this.__("You can't cross this area"), "error");
                            }
                        }
                    }
                }
            }
        });
    }

    changeCards()
    {
        if ($("#mycard li").length === 1) {
            $("#renew_cards").click();
        } else {
            $(".changecard").toggleClass("hidden");
        }
    }

    updateLemmingPosition(hex, lemming)
    {
        let audio = new Audio('/sounds/move.mp3');
        audio.play();

        //hex = new position
        hex.text = lemming.attr("data-content");
        this.currentTile = hex;

        //Remove position for the other lemming (contains class)
        let coord = "x-"+lemming.attr("data-x")+"_y-"+lemming.attr("data-y");
        grid.get({x:parseInt(lemming.attr("data-x")), y:parseInt(lemming.attr("data-y"))}).text = '';
        grid.get({x:parseInt(lemming.attr("data-x")), y:parseInt(lemming.attr("data-y"))}).draw.fill({ opacity: 1});
        document.querySelectorAll("text[class*='" + coord + "']").forEach(function (e) {
            if (e.getAttribute("class").indexOf("start") === -1 &&
                e.getAttribute("class").indexOf("finish") === -1) {
                e.parentElement.removeChild(e);
            }
        });

        //Update lemming
        $('#hexa-'+lemming.attr("data-player")+'-'+lemming.attr("data-lemming")+'-x').val(hex.x);
        $('#hexa-'+lemming.attr("data-player")+'-'+lemming.attr("data-lemming")+'-y').val(hex.y);
        lemming.attr("data-x", hex.x);
        lemming.attr("data-y", hex.y);

        let allHexa = document.querySelectorAll("polygon.cursor");
        allHexa.forEach((adjacentHexa) => {
            adjacentHexa.classList.remove('cursor');
        });
        if (this.fullPath.length < this.maxTilesPath) {
            let adjacentsHexa = this.getAdjacentHexa(hex);
            adjacentsHexa.forEach((adjacentHexa) => {
                //Polygon (tile)
                document.getElementById(adjacentHexa.draw.node.id).classList.add('cursor');
                //And text (lemming)
                allHexa = document.querySelectorAll("text.x-"+adjacentHexa.x+"_y-"+adjacentHexa.y);
                allHexa.forEach((adjacentHexa) => {
                    adjacentHexa.classList.add('cursor');
                });
            });
        }

        //Add new position
        hex.color = this.getColorLemming(lemming);
        hex.addMarker();

        if (lemming.attr("data-player") === this.currentLemming.attr("data-player")
            && lemming.attr("data-lemming") === this.currentLemming.attr("data-lemming")
        ) {
            this.path.push(hex);
        }
        this.fullPath.push(hex);

        if (this.fullPath.length === this.maxTilesPath) {
            let allHexa = document.querySelectorAll("polygon.cursor");
            allHexa.forEach((adjacentHexa) => {
                adjacentHexa.classList.remove('cursor');
            });
            allHexa = document.querySelectorAll("text.cursor");
            allHexa.forEach((adjacentHexa) => {
                adjacentHexa.classList.remove('cursor');
            });
        }

        this.isWinner();
    }

    getColorLemming(lemming)
    {
        //Fix color if no emoji (win < 10)
        let color = "#FFFFFF";
        switch (lemming.attr("data-color")) {
            case "player1":
                color = "#f80031";
                break;
            case "player2":
                color = "#5e5e5e";
                break;
            case "player3":
                color = "#7c6d1f";
                break;
            case "player4":
                color = "#37be0a";
                break;
            case "player5":
                color = "#0a53be";
                break;
        }
        return color;
    }

    isWinner()
    {
        if ($('#lemming1').length > 0 && $('#lemming2').length > 0) {
            let coord = {x: parseInt($("#lemming1").attr('data-x')), y: parseInt($("#lemming1").attr('data-y'))};
            let hex1 = grid.get(coord);
            coord = {x: parseInt($("#lemming2").attr('data-x')), y: parseInt($("#lemming2").attr('data-y'))};
            let hex2 = grid.get(coord);

            if (hex1.finish && hex2.finish) {
                let audio = new Audio('/sounds/finish.mp3');
                audio.play();
                let emojisDir = document.getElementById('emojis').value;
                Swal.fire({
                    iconHtml: '<img alt="winner" class="winner" src="/images/'+emojisDir+'/winner'+document.getElementById('icon_number').value+'.png">',
                    title: this.__("You have win"),
                    showDenyButton: false,
                    showCancelButton: false,
                    confirmButtonText: this.__('Congratulations')
                }).then(() => {
                    $("#btnConfirm").click();
                });
            }
        }
    }

    isAdjacentHexa(newHexa)
    {
        let canMove = false;
        var contiguousHexa = this.getAdjacentHexa(this.currentTile);

        contiguousHexa.forEach((hexa) => {
            if (newHexa.x === hexa.x && newHexa.y === hexa.y) {
                canMove = true;
            }
        });

        return canMove;
    }

    getAdjacentHexa(hexagone)
    {
        let adjacentsHexa = [];
        let hexagones = grid.neighborsOf(hexagone, 'all');

        hexagones.forEach((hexa) => {
            if (hexa) {
                if (hexa.landscape !== 'out' || hexa.finish) {
                    adjacentsHexa.push(hexa);
                }
            }
        });

        return adjacentsHexa;
    }

    getStartHexa()
    {
        let adjacentsHexa = [];

        grid.forEach((hexa) => {
            if (hexa && hexa.start) {
                adjacentsHexa.push(hexa);
            }
        });

        return adjacentsHexa;
    }

    resetCard()
    {
        $(".card").removeClass("selected");
        $(".hex").removeClass("path");

        $(".cards-deck").each(function () {
            $(this).html($(this).attr("data-origine"));
        });

        this.path = [];
        this.fullPath = [];
    }

    validateCardAndPath()
    {
        if (window.game.path.length === 0) {
            window.game.popin(window.game.__("You need to indicate a route"), "error");
            return false;
        } else {
            let serializedPath = [];
            window.game.path.forEach((hexa) => {
                serializedPath.push(
                    {
                        x: hexa.x,
                        y: hexa.y,
                    }
                );
            });

            $('#path').val(JSON.stringify(serializedPath));
            $('#full_path').val(JSON.stringify(serializedPath));
            window.game.disableButtons();
            return true;
        }
    }

    popin(title, icon)
    {
        let audio = new Audio('/sounds/info.mp3');
        audio.play();

        Swal.fire({
            icon: icon,
            title: title,
            showDenyButton: false,
            showCancelButton: false,
            confirmButtonText: 'OK'
        }).then(() => {

        });
    }

    popinPlaceMarker(title, icon)
    {
        let audio = new Audio('/sounds/info.mp3');
        audio.play();

        Swal.fire({
            icon: icon,
            title: title,
            showDenyButton: false,
            showCancelButton: false,
            confirmButtonText: 'OK'
        }).then(() => {
            $("#tile-hover").show();
        });
    }

    askPushLemming(title, icon, hex, direction)
    {
        Swal.fire({
            icon: icon,
            title: title,
            showDenyButton: true,
            showCancelButton: false,
            confirmButtonText: this.__('Yes'),
            denyButtonText: this.__('No')
        }).then((result) => {
            if (result.isConfirmed) {
                let otherLemmings = this.getOtherLemmingsForPush([], hex, direction);
                let nbMoves = otherLemmings.length;
                if (!this.checkPositionsForPushingLemmings(otherLemmings, direction)) {
                    this.popin(this.__("You can't push the other lemming out of the map"), "error");
                } else {
                    if ((this.fullPath.length+nbMoves) >= this.maxTilesPath) {
                        this.popin(this.__("You don't have enough moves to push the other lemming(s)"), "error");
                    } else {
                        //Start by the end
                        for (let k = otherLemmings.length -1; k >= 0; k--) {
                            let otherLemming = otherLemmings[k];
                            let hexagone = grid.neighborsOf(otherLemming,  [direction])[0];
                            let lemming = document.querySelectorAll(".lemming[data-x='"+otherLemming.x+"'][data-y='"+otherLemming.y+"']")[0];
                            this.updateLemmingPosition(hexagone, $(lemming));
                        }

                        //Move your current lemming
                        this.updateLemmingPosition(hex, this.currentLemming);
                    }
                }
            }
        });
    }

    getOtherLemmingsForPush(otherLemmings, hex, direction)
    {
        if (hex.text !== '' && hex.text !== document.getElementById('icon_start').value
            && hex.text !== document.getElementById('icon_finish').value ) {
            let hexagone = grid.neighborsOf(hex,  [direction])[0];
            otherLemmings.push(hex);
            if (hexagone) {
                otherLemmings = this.getOtherLemmingsForPush(otherLemmings, hexagone, direction);
            }
        }
        return otherLemmings;
    }

    checkPositionsForPushingLemmings(otherLemmings, direction)
    {
        let canMove = true;
        otherLemmings.forEach((hexa) => {
            let hexagone = grid.neighborsOf(hexa,  [direction])[0];
            if (hexagone) {
                if (hexagone.landscape === 'out') {
                    canMove = false;
                }
            } else {
                canMove = false;
            }
        });

        return canMove;
    }

    getDirection(oldTile, nextTile)
    {
        let direction = '';
        let directions = ['SE','SW','E','W','NW','NE'];
        directions.forEach((onlyDirection) => {
            let neighbors = grid.neighborsOf(oldTile, [onlyDirection]);
            neighbors.forEach((hexa) => {
                if (hexa) {
                    if (hexa.x === nextTile.x && hexa.y === nextTile.y ) {
                        direction = onlyDirection;
                    }
                }
            });
        });

        return direction;
    }

    info(title)
    {
        $("#info").html("");
        $("#info").removeClass('alert-success');
        if (title !== '') {
            $("#info").addClass('alert-success');
            $("#info").html("<i class='fa fa-info'></i>"+title);
        }
    }

    checkNbCardsToRenew()
    {
        if ($("#mycard li").length === 7 && $("#mycard li input:checked").length === 0) {
            this.popin(this.__("Select cards before renew them"), "error");
            return false;
        } else {
            window.game.disableButtons();
            return true;
        }
    }

    removePlayer(url)
    {
        Swal.fire({
            icon: 'question',
            title: this.__('Confirm removing this player ?'),
            showDenyButton: true,
            showCancelButton: false,
            confirmButtonText: this.__('Yes'),
            denyButtonText: this.__('No')
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = url;
            }
        });
    }

    timeOut()
    {
        Swal.fire({
            icon: 'question',
            title: this.__('Do you continue to play ? (you will be removed)'),
            showDenyButton: false,
            showCancelButton: false,
            confirmButtonText: this.__('Yes'),
            denyButtonText: this.__('No')
        }).then((result) => {
            if (result.isConfirmed) {
                ajax.timeOut(this.gameId);
            }
        });
    }

    seeLastMoves()
    {
        let moves =  JSON.parse(document.getElementById('game_lastmoves').value);
        moves.forEach((move) => {
            let hexa = grid.get({x: move.x, y:move.y});
            hexa.draw.toggleClass('lastmoves');
        });
    }

    changeMap(mapId)
    {
        ajax.changeMap(this.gameId, mapId);
    }

    __(key, replace = {})
    {
        var translation = key.split('.').reduce((t, i) => t[i] || null, window.translations);

        for (var placeholder in replace) {
            translation = translation.replace(`:${placeholder}`, replace[placeholder]);
        }
        return translation;
    }

    editTile(landscape)
    {
        window.game.placeMarkerLandscape = landscape;
        $("#tile-hover .hexagonemain").attr('class', 'hexagonemain').addClass("hex-"+landscape);
        $("#tile-hover").show();
    }

    editTileFinishStart(startOrFinish)
    {
        this.editorStartOrFinish = startOrFinish;
        this.editTile('out');
    }

    disableButtons()
    {
        $(".btn").attr("disabled",true);
        $("#app").hide();
        $("#loader").show();
    }

    removeInfos()
    {
        document.cookie = "info=false";
        $("#info").fadeOut();
    }

    getCookieValue(name)
    {
        return document.cookie.match('(^|;)\\s*' + name + '\\s*=\\s*([^;]+)')?.pop() || '';
    }
}
