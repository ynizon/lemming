let currentCard = null;
let currentLemming = null;
let currentTile = null;
let path = [];
let maxTilesPath = 0;
let landscapePath = null;
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
            }
            $('#card_id').val(cardId);
        });
    });
}

function initLemmings() {
    var hexa = $(".hex[data-x="+$( "#lemming1" ).attr('data-x')+"][data-y="+$( "#lemming1" ).attr('data-y')+"]");
    if (hexa) {
        hexa.html("<i class=\"fa fa-frog "+$( "#lemming1" ).attr('data-color')+"\"></i>");
    }
    var hexa = $(".hex[data-x="+$( "#lemming2" ).attr('data-x')+"][data-y="+$( "#lemming2" ).attr('data-y')+"]");
    if (hexa) {
        hexa.html("<i class=\"fa fa-frog "+$( "#lemming2" ).attr('data-color')+"\"></i>");
    }

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
    $( ".hex" ).on("click", function(){
        if (currentLemming) {
            if (currentCard) {
                var hexa = $(this);
                if (path.length < maxTilesPath) {
                    if (hexa.attr('data-landscape') === 'none' ||
                        hexa.attr('data-landscape') === landscapePath)
                    {
                        var canMove = false;
                        if (!currentTile) {
                            if (hexa.attr('data-event') === 'start') {
                                canMove = true;
                            } else {
                                alert('Pas une case de depart');
                            }
                        } else {
                            if (isAdjacentHexa(hexa)) {
                                canMove = true;
                            }
                        }
                        if (canMove)
                        {
                            hexa.html("<i class=\"fa fa-map-marker-alt\"></i>");
                            hexa.addClass("path");
                            path.push(hexa);
                            currentTile = hexa;
                            $('.hexa').removeClass('cursor');
                            if (path.length < maxTilesPath){
                                let adjacentsHexa = getAdjacentHexa(hexa);
                                adjacentsHexa.forEach((adjacentHexa, index) => {
                                    adjacentHexa.addClass('cursor');
                                });
                            }
                        } else {
                            if (currentTile) {
                                alert("Case non adjacente");
                            }
                        }
                    } else {
                        alert("Impossible de traverser cette case");
                    }
                } else {
                    alert("Chemin maximum dépassé");
                }
            } else {
                alert('Select a card before');
            }
        } else {
            alert("Choisi ton lemming d'abord");
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
        if (hexa.html() == '' && hexa.attr('data-landscape') !== 'out'){
            adjacentsHexa.push(hexa);
        }
        var hexa = $(".hex[data-x="+(parseInt(currentTile.attr('data-x'))-1)+"][data-y="+(parseInt(currentTile.attr('data-y')))+"]");
        if (hexa.html() == '' && hexa.attr('data-landscape') !== 'out') {
            adjacentsHexa.push(hexa);
        }
        var hexa = $(".hex[data-x="+(parseInt(currentTile.attr('data-x')))+"][data-y="+(parseInt(currentTile.attr('data-y'))-1)+"]");
        if (hexa.html() == '' && hexa.attr('data-landscape') !== 'out') {
            adjacentsHexa.push(hexa);
        }
        var hexa = $(".hex[data-x="+(parseInt(currentTile.attr('data-x')))+"][data-y="+(parseInt(currentTile.attr('data-y'))+1)+"]");
        if (hexa.html() == '' && hexa.attr('data-landscape') !== 'out') {
            adjacentsHexa.push(hexa);
        }
        var hexa = $(".hex[data-x="+(parseInt(currentTile.attr('data-x'))+1)+"][data-y="+(parseInt(currentTile.attr('data-y'))-1)+"]");
        if (hexa.html() == '' && hexa.attr('data-landscape') !== 'out') {
            adjacentsHexa.push(hexa);
        }
        var hexa = $(".hex[data-x="+(parseInt(currentTile.attr('data-x'))+1)+"][data-y="+(parseInt(currentTile.attr('data-y')))+"]");
        if (hexa.html() == '' && hexa.attr('data-landscape') !== 'out') {
            adjacentsHexa.push(hexa);
        }
    } else {
        var hexa = $(".hex[data-x="+(parseInt(currentTile.attr('data-x'))-1)+"][data-y="+(parseInt(currentTile.attr('data-y')))+"]");
        if (hexa.html() == '' && hexa.attr('data-landscape') !== 'out') {
            adjacentsHexa.push(hexa);
        }
        var hexa = $(".hex[data-x="+(parseInt(currentTile.attr('data-x'))-1)+"][data-y="+(parseInt(currentTile.attr('data-y'))+1)+"]");
        if (hexa.html() == '' && hexa.attr('data-landscape') !== 'out') {
            adjacentsHexa.push(hexa);
        }
        var hexa = $(".hex[data-x="+(parseInt(currentTile.attr('data-x')))+"][data-y="+(parseInt(currentTile.attr('data-y'))-1)+"]");
        if (hexa.html() == '' && hexa.attr('data-landscape') !== 'out') {
            adjacentsHexa.push(hexa);
        }
        var hexa = $(".hex[data-x="+(parseInt(currentTile.attr('data-x')))+"][data-y="+(parseInt(currentTile.attr('data-y'))+1)+"]");
        if (hexa.html() == '' && hexa.attr('data-landscape') !== 'out') {
            adjacentsHexa.push(hexa);
        }
        var hexa = $(".hex[data-x="+(parseInt(currentTile.attr('data-x'))+1)+"][data-y="+(parseInt(currentTile.attr('data-y')))+"]");
        if (hexa.html() == '' && hexa.attr('data-landscape') !== 'out') {
            adjacentsHexa.push(hexa);
        }
        var hexa = $(".hex[data-x="+(parseInt(currentTile.attr('data-x'))+1)+"][data-y="+(parseInt(currentTile.attr('data-y'))+1)+"]");
        if (hexa.html() == '' && hexa.attr('data-landscape') !== 'out') {
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
        alert("Vous devez indiquer un itinéraire");
        return false;
    } else {
        $('#path').val(JSON.stringify(path));
        $('#hexa-x').val(currentTile.attr('data-x'));
        $('#hexa-y').val(currentTile.attr('data-y'));
        $("#lemming_number").val(currentLemming.attr('data-lemming'));
        return true;
    }
}
