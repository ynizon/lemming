let currentCard = null;
let currentLemming = null;
let currentTile = null;
let path = [];
let maxTilesPath = 0;
let landscapePath = null;
let placeMarkerLandscape = '';
let lemmingsPositions = [];
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

            $( ".card-deck" ).each(function( index ) {
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

                info("Vous devez maintenant placer une tuile "+landscape);
            }
            $('#card_id').val(cardId);
        });
    });
}

function initLemmings() {
    $(".lemming").each(function( index ) {
        var hexa = $(".hex[data-x="+$( this ).attr('data-x')+"][data-y="+$( this ).attr('data-y')+"]");
        if (hexa) {
            hexa.html("<i class=\"fa fa-frog "+$( this ).attr('data-color')+"\"></i>");
        }
        lemmingsPositions.push($( this ).attr('data-x')+'/'+$( this ).attr('data-y'));
    });

    $( "#lemming1" ).on("click", function(){
        currentLemming = $(this);
        $(".lemming").removeClass("selected");
        $(this).addClass("selected");

        $(".hex i").removeClass("selected");
        currentTile = null;
        var hexa = $(".hex[data-x="+$( "#lemming1" ).attr('data-x')+"][data-y="+$( "#lemming1" ).attr('data-y')+"]");
        if (hexa.length > 0) {
            currentTile = hexa;
            currentTile.find('i').addClass('selected');
        }
    });

    $( "#lemming2" ).on("click", function(){
        currentLemming = $(this);
        $(".lemming").removeClass("selected");
        $(this).addClass("selected");

        $(".hex i").removeClass("selected");
        currentTile = null;
        var hexa = $(".hex[data-x="+$( "#lemming2" ).attr('data-x')+"][data-y="+$( "#lemming2" ).attr('data-y')+"]");
        if (hexa.length > 0) {
            currentTile = hexa;
            currentTile.find('i').addClass('selected');
        }
    });

    //Default Lemming is 1
    if ($('#lemming1').length > 0) {
        $('#lemming1').click();
    }
}

function initMap() {
    tilesLandscape.earth = $('#nb_earth').val();
    tilesLandscape.water = $('#nb_water').val();
    tilesLandscape.forest = $('#nb_forest').val();
    tilesLandscape.desert = $('#nb_desert').val();
    tilesLandscape.rock = $('#nb_rock').val();

    $( ".hex" ).on("click", function(){
        let hexa = $(this);
        if (placeMarkerLandscape !== ''){
            if (hexa.attr('data-landscape') === 'start' || hexa.attr('data-landscape') === 'finish'
                || hexa.attr('data-landscape') === 'out' ){
                popin('Vous ne pouvez placer votre tuile sur cet emplacement.', 'error');
            } else {
                $("#changemap-x").val(hexa.attr('data-x'));
                $("#changemap-y").val(hexa.attr('data-y'));
                $("#changemap-landscape").val(placeMarkerLandscape);
                hexa.attr('data-landscape', placeMarkerLandscape);
                hexa.attr('class','hex hex-'+placeMarkerLandscape);
                placeMarkerLandscape = '';
                info('');
            }
        } else {
            if (currentLemming) {
                if (currentCard) {
                    if (path.length < maxTilesPath) {
                        if (hexa.attr('data-landscape') === 'none' ||
                            hexa.attr('data-landscape') === 'start' ||
                            hexa.attr('data-landscape') === 'finish' ||
                            hexa.attr('data-landscape') === landscapePath) {
                            var canMove = false;
                            if (!currentTile) {
                                if (hexa.attr('data-landscape') === 'start') {
                                    canMove = true;
                                } else {
                                    info('Pas une case de depart','error');
                                }
                            } else {
                                if (isAdjacentHexa(hexa)) {
                                    canMove = true;
                                }
                            }
                            if (canMove) {
                                if (hexa.html() !== '') {
                                    popin("Case occupée", "error");
                                } else {
                                    hexa.html("<i class=\"fa fa-map-marker-alt\"></i>");
                                    hexa.addClass("path");
                                    path.push(hexa);
                                    currentTile = hexa;
                                    $('.hexa').removeClass('cursor');
                                    if (path.length < maxTilesPath) {
                                        let adjacentsHexa = getAdjacentHexa(hexa);
                                        adjacentsHexa.forEach((adjacentHexa, index) => {
                                            adjacentHexa.addClass('cursor');
                                        });
                                    }
                                }
                            } else {
                                if (currentTile) {
                                    popin("Case non adjacente", "error");
                                }
                            }
                        } else {
                            popin("Impossible de traverser cette case", "error");
                        }
                    } else {
                        popin("Chemin maximum dépassé", "error");
                    }
                } else {
                    popin('Select a card before', "error");
                }
            } else {
                popin("Choisi ton lemming d'abord", "error");
            }
        }
    });
}

function isAdjacentHexa(newHexa) {
    let canMove = false;
    //5/1 est contigue de 4/1 4/2 5/0 5/2 6/1 6/2
    //6/2 est contigue de  5/1 5/2 6/1 6/3 5 7/1 7/2
    var contiguousHexa = getAdjacentHexa(currentTile);

    contiguousHexa.forEach((hexa, index) => {
        if (newHexa.attr('id') === hexa.attr('id')) {
            canMove = true;
        }
    });

    return canMove;
}

function getAdjacentHexa(hexagone) {
    let adjacentsHexa = [];
    if (hexagone.attr('data-x')%2 === 0) {
        var hexa = $(".hex[data-x="+(parseInt(currentTile.attr('data-x'))-1)+"][data-y="+(parseInt(currentTile.attr('data-y'))-1)+"]");
        if (hexa.attr('data-landscape') !== 'out'){
            adjacentsHexa.push(hexa);
        }
        var hexa = $(".hex[data-x="+(parseInt(currentTile.attr('data-x'))-1)+"][data-y="+(parseInt(currentTile.attr('data-y')))+"]");
        if (hexa.attr('data-landscape') !== 'out') {
            adjacentsHexa.push(hexa);
        }
        var hexa = $(".hex[data-x="+(parseInt(currentTile.attr('data-x')))+"][data-y="+(parseInt(currentTile.attr('data-y'))-1)+"]");
        if (hexa.attr('data-landscape') !== 'out') {
            adjacentsHexa.push(hexa);
        }
        var hexa = $(".hex[data-x="+(parseInt(currentTile.attr('data-x')))+"][data-y="+(parseInt(currentTile.attr('data-y'))+1)+"]");
        if (hexa.attr('data-landscape') !== 'out') {
            adjacentsHexa.push(hexa);
        }
        var hexa = $(".hex[data-x="+(parseInt(currentTile.attr('data-x'))+1)+"][data-y="+(parseInt(currentTile.attr('data-y'))-1)+"]");
        if (hexa.attr('data-landscape') !== 'out') {
            adjacentsHexa.push(hexa);
        }
        var hexa = $(".hex[data-x="+(parseInt(currentTile.attr('data-x'))+1)+"][data-y="+(parseInt(currentTile.attr('data-y')))+"]");
        if (hexa.attr('data-landscape') !== 'out') {
            adjacentsHexa.push(hexa);
        }
    } else {
        var hexa = $(".hex[data-x="+(parseInt(currentTile.attr('data-x'))-1)+"][data-y="+(parseInt(currentTile.attr('data-y')))+"]");
        if (hexa.attr('data-landscape') !== 'out') {
            adjacentsHexa.push(hexa);
        }
        var hexa = $(".hex[data-x="+(parseInt(currentTile.attr('data-x'))-1)+"][data-y="+(parseInt(currentTile.attr('data-y'))+1)+"]");
        if (hexa.attr('data-landscape') !== 'out') {
            adjacentsHexa.push(hexa);
        }
        var hexa = $(".hex[data-x="+(parseInt(currentTile.attr('data-x')))+"][data-y="+(parseInt(currentTile.attr('data-y'))-1)+"]");
        if (hexa.attr('data-landscape') !== 'out') {
            adjacentsHexa.push(hexa);
        }
        var hexa = $(".hex[data-x="+(parseInt(currentTile.attr('data-x')))+"][data-y="+(parseInt(currentTile.attr('data-y'))+1)+"]");
        if (hexa.attr('data-landscape') !== 'out') {
            adjacentsHexa.push(hexa);
        }
        var hexa = $(".hex[data-x="+(parseInt(currentTile.attr('data-x'))+1)+"][data-y="+(parseInt(currentTile.attr('data-y')))+"]");
        if (hexa.attr('data-landscape') !== 'out') {
            adjacentsHexa.push(hexa);
        }
        var hexa = $(".hex[data-x="+(parseInt(currentTile.attr('data-x'))+1)+"][data-y="+(parseInt(currentTile.attr('data-y'))+1)+"]");
        if (hexa.attr('data-landscape') !== 'out') {
            adjacentsHexa.push(hexa);
        }
    }

    return adjacentsHexa;
}

function resetCard(){
    $(".card").removeClass("selected");
    $(".hex").removeClass("path");
    path = [];
}

function validateCardAndPath() {
    if (path.length == 0) {
        popin("Vous devez indiquer un itinéraire.", "error");
        return false;
    } else {
        $('#path').val(JSON.stringify(path));
        $('#hexa-x').val(currentTile.attr('data-x'));
        $('#hexa-y').val(currentTile.attr('data-y'));
        $("#lemming_number").val(currentLemming.attr('data-lemming'));
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

function info(title){
    $("#info").html("");
    $("#info").removeClass('alert-success');
    if (title !== ''){
        $("#info").addClass('alert-success');
        $("#info").html("<i class='fa fa-info'></i>&nbsp;&nbsp;"+title);
    }
}
