export let grid;
const draw = SVG(document.getElementById('hexmap'));
const Hex = Honeycomb.extendHex({
    size: 35,
    mydraw: null,

    addMarker() {
        if (this.text !== '') {
            let updateCoordY = 10;
            if (navigator.userAgent.toLowerCase().indexOf('firefox') > -1) {
                updateCoordY = 5;
            }
            let allClasses = 'x-'+this.x+'_y-'+this.y;
            if (this.start && this.text === '🚦') {
                allClasses = allClasses + ' start ';
            }
            if (this.finish && this.text === '🏁') {
                allClasses = allClasses + ' finish ';
            }

            this.mydraw.text(this.text).font({ fill: this.color })
                .addClass(allClasses)
                .move(this.coordX+3, this.coordY+updateCoordY);
        }
    },

    render(draw) {
        const { x, y } = this.toPoint()
        const corners = this.corners()
        this.mydraw = draw;
        this.start = false;
        this.finish = false;
        this.landscape = "none";
        this.picture = "/images/meadow.png";
        this.text = "";
        this.color=  "#000000";
        this.coordX = x;
        this.coordY = y;
        let allClasses = 'poly-x-'+this.x+'_y-'+this.y;
        if (this.start) {
            allClasses = this.allClasses + ' start ';
        }
        if (this.finish) {
            allClasses = this.allClasses + ' finish ';
        }

        this.allClasses = allClasses;

        this.draw = draw
            .polygon(corners.map(({ x, y }) => `${x},${y}`))
            .fill(this.picture)
            .stroke({ width: 1, color: '#fff' })
            .addClass(this.allClasses)
            .translate(x, y);
    },
    highlight() {
        this.draw
            // stop running animation
            .stop(true, true)
            .fill({ opacity: 1, color: 'aquamarine' })
            .animate(1000)
            .fill({ opacity: 0, color: 'none' })
    }
})
const Grid = Honeycomb.defineGrid(Hex);

export let game = {
    currentCard:  null,
    currentLemming : null,
    currentTile : null,
    path : [],
    maxTilesPath : 0,
    landscapePath : null,
    placeMarkerLandscape : '',
    tilesLandscape : {water:0, earth:0, rock:0, forest:0, desert:0},

    grid : null,
    isYourTurn : 0,

    sendMessage: function (gameId) {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        $.ajax({
            type: "POST",
            url: "/message/"+gameId,
            data: {message: document.getElementById("message").value},
            success: function () {
                this.loadMessages(gameId);
            }
        });
        document.getElementById("message").value = '';
    },

    loadMessages: function (gameId) {
        if (document.getElementById("message")) {
            $.getJSON("/messages/" + gameId, function (data) {
                let items = [];
                let ul = document.getElementById("messages");
                ul.innerHTML = '';
                $.each(data, function (key, val) {
                    let li = document.createElement("li");
                    li.appendChild(document.createTextNode(val.user.name + ": " + val.message));
                    ul.appendChild(li);
                });
            });

            document.querySelector('#message').addEventListener('keypress', function (e) {
                if (e.key === 'Enter') {
                    this.sendMessage(gameId);
                }
            });
        }
    },

    loadGame: function (width, height, map, gameId) {
        this.loadMessages(gameId)
        this.isYourTurn = parseInt(document.getElementById('is_your_turn').value);
        grid = Grid.rectangle({
            width: width,
            height: height,
            // render each hex, passing the draw instance
            onCreate(hex) {
                hex.render(draw);
            }
        })

        // For create new Map see utils.js
        // var deserializedGrid=createOriginalMap()
        let deserializedGrid = JSON.parse(map);

        deserializedGrid.forEach((hexa, index) => {
            let coord = {x: hexa.x, y:hexa.y};
            grid.get(coord).landscape = hexa.landscape;
            grid.get(coord).picture = hexa.picture;
            grid.get(coord).finish = hexa.finish;
            grid.get(coord).start = hexa.start;
            grid.get(coord).text = hexa.text;
            grid.get(coord).draw.fill(grid.get(coord).picture) ;
        });

        this.initCards();
        this.initMap();
        this.initLemmings();
        this.InitStartAndFinish();
    },

    initCards: function () {
        $(".yourcard").each(function (index) {
            $(this).on("click", function () {
                game.cardClick($(this));
            });
        });
    },

    cardClick: function (card) {
        if (game.isYourTurn) {
            if (game.currentCard) {
                game.resetCard();
            }
            game.currentCard = card;
            card.addClass("selected");
            let score = parseInt(card.attr('data-score'));
            let landscape = card.attr('data-landscape');
            let cardId = card.attr('data-cardid');

            game.landscapePath = landscape;

            $(".cards-deck").each(function (index) {
                card.html(card.attr("data-origine"));
            });

            let min = parseInt($('#score-' + landscape).attr("data-min"));
            if (score <= min) {
                let total = score + parseInt($('#score-' + landscape).attr("data-score"));
                game.maxTilesPath = total;
                $('#score-' + landscape).html(
                    $('#score-' + landscape).attr("data-origine") + ' + ' + score + ' = ' + total
                );
            } else {
                let total = score;
                game.maxTilesPath = total;
                $('#score-' + landscape).html(
                    score + ' = ' + total
                );
                if (game.tilesLandscape[landscape] === "0") {
                    landscape = 'meadow';
                }
                game.placeMarkerLandscape = landscape;

                game.popin(game.__("You should now replace a tile by a ") + game.__(landscape) + ".", "warning");
            }
            $('#card_id').val(cardId);
        }
    },

    InitStartAndFinish: function () {
        window.setTimeout(function () {
            grid.forEach((hexa, index) => {
                if (hexa.start) {
                    hexa.text = '🚦';
                    hexa.addMarker();
                    hexa.draw.fill("#DDDDDD") ;
                }
                if (hexa.finish) {
                    hexa.text = '🏁';
                    hexa.addMarker();
                    hexa.draw.fill("#DDDDDD") ;
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
        }, 2000);
    },

    initLemmings: function () {
        $(".lemming").each(function ( index ) {
            if ($(this).attr('data-x') !== "-1" && $(this).attr('data-y') !== "-1") {
                let coord = {x: parseInt($(this).attr('data-x')), y: parseInt($(this).attr('data-y'))};
                let hex = grid.get(coord);
                hex.text = $(this).attr("data-content");
                hex.addMarker();
            }
        });

        $("#lemming1").on("click", function () {
            game.lemmingClick(this.id);
        });

        $("#lemming2").on("click", function () {
            game.lemmingClick(this.id);
        });
    },

    lemmingClick: function (lemmingId) {
        if (game.isYourTurn) {
            if ($("#" + lemmingId).attr("data-finish") === "1") {
                game.popin(game.__("This lemming has already finished"), "error");
            } else {
                if (game.path.length > 0) {
                    game.popin(this.__("You can't move 2 lemmings"), "error");
                } else {
                    let allHexa = document.querySelectorAll("polygon.cursor");
                    allHexa.forEach((adjacentHexa, index) => {
                        adjacentHexa.classList.remove('cursor');
                    });
                    game.currentLemming = $("#" + lemmingId);
                    $(".lemming").removeClass("selected");
                    $("#" + lemmingId).addClass("selected");

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
                        game.currentTile = hex;
                        adjacentsHexa = game.getAdjacentHexa(hex);
                    } else {
                        adjacentsHexa = game.getStartHexa();
                    }

                    let icons = document.querySelectorAll("text");
                    icons.forEach((icon, index) => {
                        icon.classList.remove('cursor');
                    });
                    adjacentsHexa.forEach((adjacentHexa, index) => {
                        document.getElementById(adjacentHexa.draw.node.id).classList.add('cursor');
                        icons = document.querySelectorAll("text[class*='x-" + adjacentHexa.x + "_y-" + adjacentHexa.y + "']");
                        icons.forEach((icon, index) => {
                            icon.classList.add('cursor');
                        });
                    });
                }
            }
        }
    },

    initMap: function () {
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
                const hexCoordinates = Grid.pointToHex([correctOffsetX, correctOffsetY])
                const hex = grid.get(hexCoordinates)

                if (this.placeMarkerLandscape !== '') {
                    if (hex.start || hex.finish) {
                        this.popin(this.__("You can\'t put a tile on this area"), 'error');
                    } else {
                        $("#changemap-x").val(hex.x);
                        $("#changemap-y").val(hex.y);
                        $("#changemap-landscape").val(this.placeMarkerLandscape);

                        grid.get(hex).landscape = this.placeMarkerLandscape;
                        grid.get(hex).draw.fill('/images/' + this.placeMarkerLandscape + '.png');

                        this.placeMarkerLandscape = '';
                        this.info('');
                    }
                } else {
                    if (this.currentLemming) {
                        if (this.currentCard) {
                            if (this.path.length < this.maxTilesPath) {
                                if (hex) {
                                    if (hex.landscape === 'none' ||
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
                                                this.askPushLemming(this.__("Do you want push the other lemming ?"), "warning", hex, direction);
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
                            } else {
                                this.popin(this.__("Maximum path exceeded"), "error");
                            }
                        } else {
                            this.popin(this.__("Select a card before"), "error");
                        }
                    } else {
                        this.popin(this.__("Select your lemming first"), "error");
                    }
                }
            }
        });
    },

    updateLemmingPosition: function (hex, lemming) {
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
        allHexa.forEach((adjacentHexa, index) => {
            adjacentHexa.classList.remove('cursor');
        });
        if (this.path.length < this.maxTilesPath) {
            let adjacentsHexa = this.getAdjacentHexa(hex);
            adjacentsHexa.forEach((adjacentHexa, index) => {
                //Polygon (tile)
                document.getElementById(adjacentHexa.draw.node.id).classList.add('cursor');
                //And text (lemming)
                allHexa = document.querySelectorAll("text.x-"+adjacentHexa.x+"_y-"+adjacentHexa.y);
                allHexa.forEach((adjacentHexa, index) => {
                    adjacentHexa.classList.add('cursor');
                });
            });
        }

        //Add new position
        hex.addMarker();
        this.path.push(hex);

        if (this.path.length === this.maxTilesPath) {
            let allHexa = document.querySelectorAll("polygon.cursor");
            allHexa.forEach((adjacentHexa, index) => {
                adjacentHexa.classList.remove('cursor');
            });
        }
    },

    isAdjacentHexa: function (newHexa) {
        let canMove = false;
        var contiguousHexa = this.getAdjacentHexa(this.currentTile);

        contiguousHexa.forEach((hexa, index) => {
            if (newHexa.x === hexa.x && newHexa.y === hexa.y) {
                canMove = true;
            }
        });

        return canMove;
    },

    getAdjacentHexa : function (hexagone) {
        let adjacentsHexa = [];
        let hexagones = grid.neighborsOf(hexagone, 'all');

        hexagones.forEach((hexa, index) => {
            if (hexa) {
                if (hexa.landscape !== 'out' || hexa.finish) {
                    adjacentsHexa.push(hexa);
                }
            }
        });

        return adjacentsHexa;
    },

    getStartHexa: function () {
        let adjacentsHexa = [];

        grid.forEach((hexa, index) => {
            if (hexa && hexa.start) {
                adjacentsHexa.push(hexa);
            }
        });

        return adjacentsHexa;
    },

    resetCard: function () {
        $(".card").removeClass("selected");
        $(".hex").removeClass("path");
        this.path = [];
    },

    validateCardAndPath: function () {
        if (game.path.length === 0) {
            game.popin(game.__("You need to indicate a route"), "error");
            return false;
        } else {
            let serializedPath = [];
            game.path.forEach((hexa, index) => {
                serializedPath.push(
                    {
                        x: hexa.x,
                        y: hexa.y,
                    }
                );
            });

            $('#path').val(JSON.stringify(serializedPath));
            return true;
        }
    },

    popin: function (title, icon) {
        Swal.fire({
            icon: icon,
            title: title,
            showDenyButton: false,
            showCancelButton: false,
            confirmButtonText: 'OK'
        }).then((result) => {

        });
    },

    askPushLemming: function (title, icon, hex, direction) {
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
                    if ((this.path.length+nbMoves) >= this.maxTilesPath) {
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
                        this.updateLemmingPosition(hex, currentLemming);
                    }
                }
            }
        });
    },

    getOtherLemmingsForPush: function (otherLemmings, hex, direction) {
        if (hex.text !== '') {
            let hexagone = grid.neighborsOf(hex,  [direction])[0];
            otherLemmings.push(hex);
            otherLemmings = getOtherLemmingsForPush(otherLemmings, hexagone, direction);
        }
        return otherLemmings;
    },

    checkPositionsForPushingLemmings: function (otherLemmings, direction) {
        let canMove = true;
        otherLemmings.forEach((lemming, index) => {
            let hexagone = grid.neighborsOf(lemming,  [direction])[0];
            if (hexagone.landscape === 'out') {
                canMove = false;
            }
        });

        return canMove;
    },

    getDirection: function (oldTile, nextTile) {
        let direction = '';
        let directions = ['SE','SW','E','W','NW','NE'];
        directions.forEach((onlyDirection, index) => {
            let neighbors = grid.neighborsOf(oldTile, [onlyDirection]);
            neighbors.forEach((hexa, index) => {
                if (hexa) {
                    if (hexa.x === nextTile.x && hexa.y === nextTile.y ) {
                        direction = onlyDirection;
                    }
                }
            });
        });

        return direction;
    },

    info: function (title) {
        $("#info").html("");
        $("#info").removeClass('alert-success');
        if (title !== '') {
            $("#info").addClass('alert-success');
            $("#info").html("<i class='fa fa-info'></i>"+title);
        }
    },

    checkNbCardsToRenew: function () {
        if ($("#mycard li").length === 7 && $("#mycard li input:checked").length === 0) {
            this.popin(this.__("Select cards before renew them"), "error");
            return false;
        } else {
            return true;
        }
    },

    __: function (key, replace = {}) {
        var translation = key.split('.').reduce((t, i) => t[i] || null, window.translations);

        for (var placeholder in replace) {
            translation = translation.replace(`:${placeholder}`, replace[placeholder]);
        }
        return translation;
    },

    createOriginalMap: function () {
        //x = column, y = row
        let tiles = [
            {x: 0, y: 0 },{x: 1, y: 0 },{x: 2, y: 0 },{x: 3, y: 0 },{x: 4, y: 0 },{x: 5, y: 0 },{x: 6, y: 0 },{x: 7, y: 0 },{x: 8, y: 0 },{x: 9, y: 0 },{x: 10, y: 0 },{x: 11, y: 0 },{x: 12, y: 0 },{x: 13, y: 0 },{x: 14, y: 0 },{x: 15, y: 0 },{x: 16, y: 0 },
            {x: 0, y: 1 },{x: 1, y: 1 },{x: 2, y: 1 },{x: 3, y: 1 },{x: 4, y: 1 },{x: 5, y: 1 },{x: 13, y: 1 },{x: 14, y: 1 }, {x: 15, y: 1 },{x: 16, y: 1 },
            {x: 0, y: 2 },{x: 1, y: 2 },{x: 2, y: 2 },{x: 3, y: 2 },{x: 4, y: 2 },{x: 5, y: 2 }, {x: 15, y: 2 },{x: 16, y: 2 },
            {x: 0, y: 3 },{x: 1, y: 3 }, {x: 2, y: 3 },{x: 3, y: 3 },{x: 4, y: 3 }, {x: 15, y: 3 },{x: 16, y: 3 },
            {x: 0, y: 4 },{x: 1, y: 4 }, {x: 2, y: 4 },{x: 3, y: 4 },{x: 4, y: 4 }, {x: 16, y: 4 },
            {x: 3, y: 5 },{x: 4, y: 5 },{x: 16, y: 5 },
            {x: 4, y: 6 },{x: 5, y: 6 },{x: 6, y: 6 },{x: 7, y: 6 },{x: 8, y: 6 },{x: 9, y: 6 },{x: 10, y: 6 },{x: 11, y: 6 },{x: 16, y: 6 },
            {x: 4, y: 7 },{x: 5, y: 7 },{x: 6, y: 7 },{x: 7, y: 7 },{x: 8, y: 7 },{x: 9, y: 7 },{x: 10, y: 7 },{x: 11, y: 7 },{x: 16, y: 7 },
            {x: 5, y: 8 },{x: 6, y: 8 },{x: 10, y: 8 },{x: 11, y: 8 },{x: 16, y: 8 },
            {x: 16, y: 9 },
            {x: 0, y: 10 },{x: 16, y: 10 },
            {x: 0, y: 11 },{x: 15, y: 11 },{x: 16, y: 11 },
            {x: 0, y: 12 },{x: 1, y: 12 },{x: 15, y: 12 },{x: 16, y: 12 },
            {x: 0, y: 13 },{x: 1, y: 13 },{x: 2, y: 13 },{x: 13, y: 13 },{x: 14, y: 13 },{x: 15, y: 13 },{x: 16, y: 13 },
        ];
        tiles.forEach((hexa, index) => {
            grid.get(hexa).landscape = "out";
            grid.get(hexa).picture = 'none';
        });

        tiles = [
            {x: 9, y: 1 },{x: 10, y: 1 },
            {x: 10, y: 2 },
            {x: 3, y: 8 },{x: 4, y: 8 },
            {x: 4, y: 9 },
            {x: 5, y: 13 },{x: 6, y: 13 },{x: 7, y: 13 },
        ];
        tiles.forEach((hexa, index) => {
            grid.get(hexa).landscape = "rock";
            grid.get(hexa).picture = '/images/rock.png';
        });

        tiles = [
            {x: 15, y: 5 },
            {x: 1, y: 6 },{x: 15, y: 6 },
            {x: 15, y: 7 },
            {x: 12, y: 8 },{x: 13, y: 8 },
            {x: 13, y: 9 },
            {x: 14, y: 10 },
            {x: 12, y: 11 },{x: 13, y: 11 },
        ];
        tiles.forEach((hexa, index) => {
            grid.get(hexa).landscape = "desert";
            grid.get(hexa).picture = '/images/desert.png';
        });

        tiles = [
            {x: 8, y: 2 },{x: 7, y: 2 },
            {x: 6, y: 3 },{x: 7, y: 3 },
            {x: 1, y: 9 },{x: 5, y: 9 },
            {x: 2, y: 10 },{x: 3, y: 10 },{x: 4, y: 10 },
            {x: 3, y: 11 },
            {x: 3, y: 13 },{x: 4, y: 13 },
        ];
        tiles.forEach((hexa, index) => {
            grid.get(hexa).landscape = "earth";
            grid.get(hexa).picture = '/images/earth.png';
        });

        tiles = [
            {x: 12, y: 4 },
            {x: 11, y: 5 },{x: 12, y: 5 },{x: 13, y: 5 },
            {x: 11, y: 13 },
        ];
        tiles.forEach((hexa, index) => {
            grid.get(hexa).landscape = "water";
            grid.get(hexa).picture = '/images/water.png';
        });

        tiles = [
            {x: 12, y: 2 },
            {x: 12, y: 3 },{x: 13, y: 3 },
            {x: 13, y: 4 },{x: 14, y: 4 },
            {x: 7, y: 5 },{x: 8, y: 5 },
            {x: 12, y: 6 },{x: 13, y: 6 },
            {x: 12, y: 7 },
            {x: 12, y: 9 },
            {x: 13, y: 10 },
        ];
        tiles.forEach((hexa, index) => {
            grid.get(hexa).landscape = "forest";
            grid.get(hexa).picture = '/images/forest.png';
        });

        //Needs to have 2 starting points !
        tiles = [
            {x: 1, y: 4 },
            {x: 2, y: 4 },
        ];
        tiles.forEach((hexa, index) => {
            grid.get(hexa).start = true;
            grid.get(hexa).landscape = "none";
            grid.get(hexa).picture = '/images/start.png';
        });

        tiles = [
            {x: 5, y: 1 },
            {x: 5, y: 2 },
            {x: 4, y: 3 },
        ];
        tiles.forEach((hexa, index) => {
            grid.get(hexa).finish = true;
            grid.get(hexa).landscape = "none";
            grid.get(hexa).picture = '/images/finish.png';
        });

        let serializedGrid = [];
        grid.forEach((hexa, index) => {
            hexa.draw.fill(hexa.picture) ;

            serializedGrid.push(
                {
                    picture: hexa.picture,
                    landscape: hexa.landscape,
                    x: hexa.x,
                    y: hexa.y,
                    coordX: hexa.coordX,
                    coordY: hexa.coordY,
                    start: hexa.start,
                    finish: hexa.finish,
                    text: hexa.text
                }
            );
        });
        serializedGrid = JSON.stringify(serializedGrid);
        return JSON.parse(serializedGrid);
    }
}
