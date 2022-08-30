const Honeycomb = require('honeycomb-grid');

let sizeIcon = 35;

const vw = Math.max(document.documentElement.clientWidth || 0, window.innerWidth || 0)
if (vw < 1550) {
    sizeIcon = 30;
    document.getElementById('hexmap').classList.add('map-35');
}
if (vw < 1370) {
    sizeIcon = 25;
    document.getElementById('hexmap').classList.add('map-25');
}
const draw = SVG(document.getElementById('hexmap'));
const Hex = Honeycomb.extendHex({
    size: sizeIcon,
    mydraw: null,

    addMarker() {
        if (this.text !== '') {
            let updateCoordY = 10;
            if (navigator.userAgent.toLowerCase().indexOf('firefox') > -1) {
                updateCoordY = 5;
            }
            let allClasses = 'x-'+this.x+'_y-'+this.y;
            if (this.start && this.text ===  document.getElementById("icon_start").value) {
                allClasses = allClasses + ' start ';
            }
            if (this.finish && this.text ===  document.getElementById("icon_finish").value) {
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
        this.editorStartOrFinish = '';
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
            .stroke({ width: 0, color: 'none' })
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

export const Grid = Honeycomb.defineGrid(Hex);
export let grid = null;
if (document.getElementById("hexmap")) {
    grid = Grid.rectangle({
        width: mapWidth,
        height: mapHeight,
        // render each hex, passing the draw instance
        onCreate(hex) {
            hex.render(draw);
        }
    });
}
