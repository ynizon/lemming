<h1>Lemming</h1> 

## About

Lemming Game (not Lemmings) revisited in PHP / JS.<br/>
It's a race on an hexagon map.

See https://www.trictrac.net/jeu-de-societe/lemming

Demo on https://lemming.gameandme.fr

<img src="/public/images/screenshot.png" />

## Installation with Docker (only on Linux)
- copy .env.docker.example to .env .
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate --seed

## Installation witout Docker
- copy .env.example to .env and configure it.
- create the lemming database 
````  
composer install    
php artisan key:generate
php artisan migrate --seed
````  

## Compiling asset (if needed)
Modify resources/css/app.css
Modify resources/js/app.js
````  
npm run dev    
````  

## Pusher
If you want refresh your browser when your opponent has played, you need to 
create an account on pusher.com and set your app in .env like this:
````  
BROADCAST_DRIVER=pusher
PUSHER_APP_ID=""
PUSHER_APP_KEY=""
PUSHER_APP_SECRET=""
PUSHER_APP_CLUSTER="eu"
````  

## Maps
- For create new map, modify the game.js > createOriginalMap function
- Start a new game
- Edit the json from the map table

## Quality
- PHPCodeSniffer
- PHPCS
- PHPMD

## License
This application use the [MIT] licence.

## Thanks
- Laravel : https://laravel.com 
- Hexagon JS : https://github.com/flauwekeul/honeycomb
- jQuery : https://jquery.com/
- SweetAlert : https://sweetalert2.github.io/
- Resources map : https://gamedev.stackexchange.com/questions/6382/how-to-create-a-hexagon-world-map-in-php-from-a-database-for-a-browser-based-str
- Emojis : https://openmoji.org/ / https://emojicombos.com/animal

## Troobleshooting
- If you haven't emojis on the map (windows < 10), please install this font: 
https://drive.google.com/file/d/1tGCTbkklfS19hfb2OC7TZGC4FmCK4-46/view

## Contact
ynizon@gmail.com
