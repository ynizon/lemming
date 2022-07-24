<h1>Lemming</h1> 

## About

Lemming Game revisited in PHP / JS.<br/>
See https://www.trictrac.net/jeu-de-societe/lemming

## Installation
- Fill .env file (see .env.example)
````  
composer install    
php artisan key:generate
php artisan migrate --seed
php artisan config:clear
php artisan cache:clear   
````  
## TODO
- Update shuffle start case (fixed in map at the beginning)
- Add Websocket / Chat

## License

This application use the [MIT] licence.

## Thanks
- Hexagon JS : https://github.com/flauwekeul/honeycomb
- jQuery : https://jquery.com/
- Resources map : https://gamedev.stackexchange.com/questions/6382/how-to-create-a-hexagon-world-map-in-php-from-a-database-for-a-browser-based-str
- Emoji : https://emojicombos.com/animal
