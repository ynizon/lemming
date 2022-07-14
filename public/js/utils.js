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
