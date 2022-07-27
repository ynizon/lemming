<h1>Lemming</h1> 

## About

Lemming Game (not Lemmings) revisited in PHP / JS.<br/>
It's a race on an hexagon map.
See https://www.trictrac.net/jeu-de-societe/lemming
<img src="/public/images/screenshot.png" />

## Installation with Docker
./vendor/bin/sail up -d

## Installation witout Docker
- create the database lemming
- copy .env.example to .env and configure it.
````  
composer install    
php artisan key:generate
php artisan migrate --seed
````  

## Maps
- For create new map, modify the utils.js > createOriginalMap function
- Start a new game
- Edit the json from the map table

## Quality
- PHPCodeSniffer
- PHPCS
- PHPMD

## TODO
- Add Websocket / Chat

## License
This application use the [MIT] licence.

## Thanks
- Hexagon JS : https://github.com/flauwekeul/honeycomb
- jQuery : https://jquery.com/
- Resources map : https://gamedev.stackexchange.com/questions/6382/how-to-create-a-hexagon-world-map-in-php-from-a-database-for-a-browser-based-str
- Emoji : https://emojicombos.com/animal
