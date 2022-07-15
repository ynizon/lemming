function handle_map_click(event) {
    // ----------------------------------------------------------------------
    // --- This function gets a mouse click on the map, converts the click to
    // --- hex map coordinates, then moves the highlight image to be over the
    // --- clicked on hex.
    // ----------------------------------------------------------------------

    // ----------------------------------------------------------------------
    // --- Determine coordinate of map div as we want the click coordinate as
    // --- we want the mouse click relative to this div.
    // ----------------------------------------------------------------------

    // ----------------------------------------------------------------------
    // --- Code based on http://www.quirksmode.org/js/events_properties.html
    // ----------------------------------------------------------------------
    var posx = 0;
    var posy = 0;
    if (event.pageX || event.pageY) {
        posx = event.pageX;
        posy = event.pageY;
    } else if (event.clientX || e.clientY) {
        posx = event.clientX + document.body.scrollLeft
            + document.documentElement.scrollLeft;
        posy = event.clientY + document.body.scrollTop
            + document.documentElement.scrollTop;
    }
    // --- Apply offset for the map div
    var map = document.getElementById('hexmap');
    posx = posx - map.offsetLeft;
    posy = posy - map.offsetTop;
    //console.log ("posx = " + posx + ", posy = " + posy);

    // ----------------------------------------------------------------------
    // --- Convert mouse click to hex grid coordinate
    // --- Code is from http://www-cs-students.stanford.edu/~amitp/Articles/GridToHex.html
    // ----------------------------------------------------------------------
    x = (posx - (hex_height/2)) / (hex_height * 0.75);
    y = (posy - (hex_height/2)) / hex_height;
    z = -0.5 * x - y;
    y = -0.5 * x + y;

    ix = Math.floor(x+0.5);
    iy = Math.floor(y+0.5);
    iz = Math.floor(z+0.5);
    s = ix + iy + iz;
    if (s) {
        abs_dx = Math.abs(ix-x);
        abs_dy = Math.abs(iy-y);
        abs_dz = Math.abs(iz-z);
        if (abs_dx >= abs_dy && abs_dx >= abs_dz) {
            ix -= s;
        } else if (abs_dy >= abs_dx && abs_dy >= abs_dz) {
            iy -= s;
        } else {
            iz -= s;
        }
    }

    // ----------------------------------------------------------------------
    // --- map_x and map_y are the map coordinates of the click
    // ----------------------------------------------------------------------
    map_x = ix;
    map_y = (iy - iz + (1 - ix %2 ) ) / 2 - 0.5;

    // ----------------------------------------------------------------------
    // --- Calculate coordinates of this hex.  We will use this
    // --- to place the highlight image.
    // ----------------------------------------------------------------------
    tx = map_x * hex_side * 1.5;
    ty = map_y * hex_height + (map_x % 2) * (hex_height / 2);

    // ----------------------------------------------------------------------
    // --- Get the highlight image by ID
    // ----------------------------------------------------------------------
    var highlight = document.getElementById('highlight');

    // ----------------------------------------------------------------------
    // --- Set position to be over the clicked on hex
    // ----------------------------------------------------------------------
    highlight.style.left = tx + 'px';
    highlight.style.top = ty + 'px';
}

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
            var score = $(this).attr('data-score');
            var landscape = $(this).attr('data-landscape');

            //TODO additionner les cartes si egale ou inferieure/ retirer les anciennes si plus fortes
            maxTilesPath = score;
            landscapePath = landscape;
        });
    });
}

function initLemmings() {
    $( "#lemming1" ).on("click", function(){
        currentLemming = $(this);
        $(".lemming").removeClass("selected");
        $(this).addClass("selected");
        var hexa = $(".hex[data-x="+$(this).attr('data-x')+"][data-y="+$(this).attr('data-y')+"]");

        if (hexa.length) {
            hexa.html("<i class=\"fa fa-frog "+$(this).attr('data-color')+"\"></i>");
        }
    });

    $( "#lemming2" ).on("click", function(){
        currentLemming = $(this);
        $(".lemming").removeClass("selected");
        $(this).addClass("selected");
    });
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
                            if (isContiguousHexa(hexa)) {
                                canMove = true;
                            }
                        }
                        if (canMove)
                        {
                            hexa.html("<i class=\"fa fa-map-marker-alt\"></i>");
                            hexa.addClass("path");
                            path.push(hexa);
                            currentTile = hexa;
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

function isContiguousHexa(newHexa) {
    let canMove = false;
    //5/1 est contigue de 4/1 4/2 5/0 5/2 6/1 6/2
    //6/2 est contigue de  5/1 5/2 6/1 6/3 5 7/1 7/2
    var contiguousHexa = [];
    if (currentTile.attr('data-x')%2 === 0) {
        var hexa = $(".hex[data-x="+(parseInt(currentTile.attr('data-x'))-1)+"][data-y="+(parseInt(currentTile.attr('data-y'))-1)+"]");
        contiguousHexa.push(hexa);
        var hexa = $(".hex[data-x="+(parseInt(currentTile.attr('data-x'))-1)+"][data-y="+(parseInt(currentTile.attr('data-y')))+"]");
        contiguousHexa.push(hexa);
        var hexa = $(".hex[data-x="+(parseInt(currentTile.attr('data-x')))+"][data-y="+(parseInt(currentTile.attr('data-y'))-1)+"]");
        contiguousHexa.push(hexa);
        var hexa = $(".hex[data-x="+(parseInt(currentTile.attr('data-x')))+"][data-y="+(parseInt(currentTile.attr('data-y'))+1)+"]");
        contiguousHexa.push(hexa);
        var hexa = $(".hex[data-x="+(parseInt(currentTile.attr('data-x'))+1)+"][data-y="+(parseInt(currentTile.attr('data-y'))-1)+"]");
        contiguousHexa.push(hexa);
        var hexa = $(".hex[data-x="+(parseInt(currentTile.attr('data-x'))+1)+"][data-y="+(parseInt(currentTile.attr('data-y')))+"]");
        contiguousHexa.push(hexa);
    } else {
        var hexa = $(".hex[data-x="+(parseInt(currentTile.attr('data-x'))-1)+"][data-y="+(parseInt(currentTile.attr('data-y')))+"]");
        contiguousHexa.push(hexa);
        var hexa = $(".hex[data-x="+(parseInt(currentTile.attr('data-x'))-1)+"][data-y="+(parseInt(currentTile.attr('data-y'))+1)+"]");
        contiguousHexa.push(hexa);
        var hexa = $(".hex[data-x="+(parseInt(currentTile.attr('data-x')))+"][data-y="+(parseInt(currentTile.attr('data-y'))-1)+"]");
        contiguousHexa.push(hexa);
        var hexa = $(".hex[data-x="+(parseInt(currentTile.attr('data-x')))+"][data-y="+(parseInt(currentTile.attr('data-y'))+1)+"]");
        contiguousHexa.push(hexa);
        var hexa = $(".hex[data-x="+(parseInt(currentTile.attr('data-x'))+1)+"][data-y="+(parseInt(currentTile.attr('data-y')))+"]");
        contiguousHexa.push(hexa);
        var hexa = $(".hex[data-x="+(parseInt(currentTile.attr('data-x'))+1)+"][data-y="+(parseInt(currentTile.attr('data-y'))+1)+"]");
        contiguousHexa.push(hexa);
    }

    contiguousHexa.forEach((hexa, index) => {
        if (newHexa.attr('id') === hexa.attr('id')) {
            canMove = true;
        }
    })

    return canMove;
}
function resetCard(){
    $(".card").removeClass("selected");
    $(".hex").removeClass("path");
    path = [];
}

function validateCardAndPath() {

}
