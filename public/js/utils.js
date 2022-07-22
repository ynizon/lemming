let currentCard = null;
let currentLemming = null;
let currentTile = null;
let path = [];
let maxTilesPath = 0;
let landscapePath = null;
let placeMarkerLandscape = '';
let tilesLandscape = {water:0, earth:0, rock:0, forest:0, desert:0};

function initCards() {
    $( ".card" ).each(function(index) {
        $(this).on("click", function(){
            if (currentCard) {
                resetCard();
            }
            currentCard = $(this);
            $(this).addClass("selected");
            let score = parseInt($(this).attr('data-score'));
            let landscape = $(this).attr('data-landscape');
            let cardId = $(this).attr('data-cardid');

            landscapePath = landscape;

            $( ".cards-deck" ).each(function( index ) {
                $( this ).html($( this ).attr("data-origine"));
            });

            let min = parseInt($('#score-'+landscape).attr("data-min"));
            if (score <= min){
                let total = score + parseInt($('#score-'+landscape).attr("data-score"));
                maxTilesPath = total;
                $('#score-'+landscape).html(
                    $('#score-'+landscape).attr("data-origine") + ' + ' + score +' = ' + total
                );
            } else {
                let total = score;
                maxTilesPath = total;
                $('#score-'+landscape).html(
                    score +' = ' + total
                );
                if (tilesLandscape[landscape] === "0"){
                    landscape = 'none';
                }
                placeMarkerLandscape = landscape;

                info(__("You should now replace a tile by a ")+__(landscape)+".");
            }
            $('#card_id').val(cardId);
        });
    });
}

function initLemmings() {
    $(".lemming").each(function( index ) {
        if ($( this ).attr('data-x') !== "-1" && $( this ).attr('data-y') !== "-1") {
            let coord = {x: parseInt($(this).attr('data-x')), y: parseInt($(this).attr('data-y'))};
            let hex = grid.get(coord);
            if (!hex.start) {
                hex.text = $(this).attr("data-content");
                hex.addMarker();
            }
        }
    });

    $( "#lemming1" ).on("click", function(){
        lemmingClick(this.id);
    });

    $( "#lemming2" ).on("click", function(){
        lemmingClick(this.id);
    });

    //Default Lemming is 1
    if ($('#lemming1').length > 0) {
        $('#lemming1').click();
    }
}

function lemmingClick(lemmingId) {
    if (path.length > 0) {
        popin(__("You can't move 2 lemmings"), "error");
    } else {
        let allHexa = document.querySelectorAll("polygon.cursor");
        allHexa.forEach((adjacentHexa, index) => {
            adjacentHexa.classList.remove('cursor');
        });
        currentLemming = $("#" + lemmingId);
        $(".lemming").removeClass("selected");
        $("#" + lemmingId).addClass("selected");

        $("polygon").removeClass("selected");
        let adjacentsHexa = [];
        currentTile = null;
        if ($("#" + lemmingId).attr('data-x') !== "-1" && $("#" + lemmingId).attr('data-y') !== "-1") {
            let coord = {
                x: parseInt($("#" + lemmingId).attr('data-x')),
                y: parseInt($("#" + lemmingId).attr('data-y'))
            };
            let hex = grid.get(coord);
            document.getElementById(hex.draw.node.id).classList.add('selected');
            currentTile = hex;
            adjacentsHexa = getAdjacentHexa(hex);
        } else {
            adjacentsHexa = getStartHexa();
        }

        icons = document.querySelectorAll("text");
        icons.forEach((icon, index) => {
            icon.classList.remove('cursor');
        });
        adjacentsHexa.forEach((adjacentHexa, index) => {
            document.getElementById(adjacentHexa.draw.node.id).classList.add('cursor');
            icons = document.querySelectorAll("text.x-" + adjacentHexa.x + "_y-" + adjacentHexa.y);
            icons.forEach((icon, index) => {
                icon.classList.add('cursor');
            });
        });
    }
}

function initMap() {
    tilesLandscape.earth = $('#nb_earth').val();
    tilesLandscape.water = $('#nb_water').val();
    tilesLandscape.forest = $('#nb_forest').val();
    tilesLandscape.desert = $('#nb_desert').val();
    tilesLandscape.rock = $('#nb_rock').val();

    grid.forEach((hexa, index) => {
        if (hexa.start) {
            hexa.text = '🚦';
            hexa.addMarker();
        }
    });

    var hexmap = document.querySelector('#hexmap');
    document.addEventListener('click', ({ offsetX, offsetY }) => {
        if (hexmap.contains(event.target)) {
            const hexCoordinates = Grid.pointToHex([offsetX, offsetY])
            const hex = grid.get(hexCoordinates)

            if (placeMarkerLandscape !== '') {
                if (hex.start || hex.finish) {
                    popin(__("You can\'t put a tile on this area"), 'error');
                } else {
                    $("#changemap-x").val(hex.x);
                    $("#changemap-y").val(hex.y);
                    $("#changemap-landscape").val(placeMarkerLandscape);

                    grid.get(hex).landscape = placeMarkerLandscape;
                    grid.get(hex).draw.fill('/images/' + placeMarkerLandscape + '.png');

                    placeMarkerLandscape = '';
                    info('');
                }
            } else {
                if (currentLemming) {
                    if (currentCard) {
                        if (path.length < maxTilesPath) {
                            if (hex.landscape === 'none' ||
                                hex.landscape === landscapePath ||
                                hex.start ||
                                hex.finish) {
                                var canMove = false;
                                if (!currentTile) {
                                    if (hex.start) {
                                        canMove = true;
                                    } else {
                                        info(__("This is not a start area"),'error');
                                    }
                                } else {
                                    if (isAdjacentHexa(hex)) {
                                        canMove = true;
                                    }
                                }
                                if (canMove) {
                                    let direction = getDirection(currentTile, hex);
                                    if (hex.text !== '') {
                                        askPushLemming(__("Do you want push the other lemming ?"), "warning", hex, direction);
                                    } else {
                                        updateLemmingPosition(hex, currentLemming, direction);
                                    }
                                } else {
                                    if (currentTile) {
                                        popin(__("This tile is not accessible"), "error");
                                    }
                                }
                            } else {
                                if (hex.landscape !== 'out') {
                                    popin(__("You can't cross this area"), "error");
                                }
                            }
                        } else {
                            popin(__("Maximum path exceeded"), "error");
                        }
                    } else {
                        popin(__("Select a card before"), "error");
                    }
                } else {
                    popin(__("Select your lemming first"), "error");
                }
            }
        }
    });
}

function updateLemmingPosition(hex, lemming, direction) {
    //hex = new position
    hex.text = lemming.attr("data-content");
    currentTile = hex;

    //Remove position for the other lemming (contains class)
    let coord = "x-"+lemming.attr("data-x")+"_y-"+lemming.attr("data-y");
    grid.get({x:parseInt(lemming.attr("data-x")), y:parseInt(lemming.attr("data-y"))}).text = '';
    document.querySelectorAll("text[class*='" + coord + "']").forEach(function (e) {
        e.parentElement.removeChild(e);
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
    if (path.length < maxTilesPath) {
        let adjacentsHexa = getAdjacentHexa(hex);
        adjacentsHexa.forEach((adjacentHexa, index) => {
            document.getElementById(adjacentHexa.draw.node.id).classList.add('cursor');
            //Wait a little
            try{
                if ($("text.x-"+adjacentHexa.x+"_y-"+adjacentHexa.y)) {
                    $("text.x-"+adjacentHexa.x+"_y-"+adjacentHexa.y).classList.add('cursor');
                }
            }catch(e){
                //@TODO pq ca plante ???
                //console.error(e);
            }
        });
    }

    //Add new position
    hex.addMarker();
    path.push(hex);

    if (path.length === maxTilesPath) {
        let allHexa = document.querySelectorAll("polygon.cursor");
        allHexa.forEach((adjacentHexa, index) => {
            adjacentHexa.classList.remove('cursor');
        });
    }
}

function isAdjacentHexa(newHexa) {
    let canMove = false;
    var contiguousHexa = getAdjacentHexa(currentTile);

    contiguousHexa.forEach((hexa, index) => {
        if (newHexa.x === hexa.x && newHexa.y === hexa.y) {
            canMove = true;
        }
    });

    return canMove;
}

function getAdjacentHexa(hexagone) {
    let adjacentsHexa = [];
    let hexagones = grid.neighborsOf(hexagone, 'all');

    hexagones.forEach((hexa, index) => {
        if (hexa && hexa.landscape !== 'out') {
            adjacentsHexa.push(hexa);
        }
    });

    return adjacentsHexa;
}

function getStartHexa() {
    let adjacentsHexa = [];

    grid.forEach((hexa, index) => {
        if (hexa && hexa.start) {
            adjacentsHexa.push(hexa);
        }
    });

    return adjacentsHexa;
}

function resetCard(){
    $(".card").removeClass("selected");
    $(".hex").removeClass("path");
    path = [];
}

function validateCardAndPath() {
    if (path.length === 0) {
        popin(__("You need to indicate a route"), "error");
        return false;
    } else {
        let serializedPath = [];
        path.forEach((hexa, index) => {
            serializedPath.push(
                {
                    x: hexa.x,
                    y: hexa.y,
                });
        });

        $('#path').val(JSON.stringify(serializedPath));
        return true;
    }
}

function popin(title, icon){
    Swal.fire({
        icon: icon,
        title: title,
        showDenyButton: false,
        showCancelButton: false,
        confirmButtonText: 'OK'
    }).then((result) => {

    });
}

function askPushLemming(title, icon, hex, direction){
    Swal.fire({
        icon: icon,
        title: title,
        showDenyButton: true,
        showCancelButton: false,
        confirmButtonText: __('Yes'),
        denyButtonText: __('No')
    }).then((result) => {
        if (result.isConfirmed) {
            if ((path.length+1) >= maxTilesPath) {
                popin(__("You don't have enough moves to push the other lemming"), "error");
            }
            if (hex.landscape === 'none' ||
                hex.landscape === landscapePath ||
                hex.start ||
                hex.finish) {

                let hexagone = grid.neighborsOf(hex,  [direction])[0];
                if (hexagone.text === '') {
                    if (hexagone.landscape !== 'out') {
                        //Move the other lemming
                        let otherLemmings = document.querySelectorAll(".lemming");
                        let otherLemming;
                        otherLemmings.forEach((lemming, index) => {
                            if (parseInt($(lemming).attr("data-x")) === hex.x &&
                                parseInt($(lemming).attr("data-y")) === hex.y){
                                otherLemming = lemming;
                            }
                        });
                        updateLemmingPosition(hexagone, $(otherLemming), direction);

                        //Move your lemming
                        updateLemmingPosition(hex, currentLemming, direction);
                    } else {
                        popin(__("You can't push the other lemming out of the map"), "error");
                    }
                } else {
                    popin(__("You can't push 2 lemmings"), "error");
                }
            } else {
                if (hex.landscape !== 'out') {
                    popin(__("You can't cross this area"), "error");
                }
            }
        }
    });
}

function getDirection(oldTile, nextTile) {
    let direction = '';
    if (oldTile.x === nextTile.x && oldTile.y === (nextTile.y-1)) {
        direction = 'NE';
    }
    if (oldTile.x === nextTile.x && oldTile.y === (nextTile.y+1)) {
        direction = 'SW';
    }
    if (oldTile.x === (nextTile.x-1) && oldTile.y === nextTile.y) {
        direction = 'W';
    }
    if (oldTile.x === (nextTile.x+1) && oldTile.y === (nextTile.y+1)) {
        direction = 'SE';
    }
    if (oldTile.x === nextTile.x && oldTile.y === (nextTile.y+1)) {
        direction = 'NW';
    }
    if (oldTile.x === (nextTile.x+1) && oldTile.y === nextTile.y) {
        direction = 'E';
    }
    return direction;
}

function info(title){
    $("#info").html("");
    $("#info").removeClass('alert-success');
    if (title !== ''){
        $("#info").addClass('alert-success');
        $("#info").html("<i class='fa fa-info'></i>"+title);
    }
}

function __(key, replace = {}) {
    var translation = key.split('.').reduce((t, i) => t[i] || null, window.translations);

    for (var placeholder in replace) {
        translation = translation.replace(`:${placeholder}`, replace[placeholder]);
    }
    return translation;
}

function createOriginalMap() {
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

    tiles = [
        {x: 0, y: 5 },
        {x: 1, y: 5 },
        {x: 2, y: 5 },
    ];
    tiles.forEach((hexa, index) => {
        grid.get(hexa).start = true;
        grid.get(hexa).landscape = "none";
        grid.get(hexa).picture = '/images/start.png';
    });

    tiles = [
        {x: 6, y: 1 },
        {x: 6, y: 2 },
        {x: 5, y: 3 },
        {x: 5, y: 4 },
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
            });
    });
    serializedGrid = JSON.stringify(serializedGrid);
    return JSON.parse(serializedGrid);
}
