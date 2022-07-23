<h1>Lemming</h1> 

## A propos

Cette application a pour but de recréer le jeu Lemming.
https://www.trictrac.net/jeu-de-societe/lemming

## Installation

- Remplir le fichier .env
- Créér la base de données
- Lancer les commandes:
````  
composer install    
php artisan key:generate
php artisan migrate --seed
php artisan config:clear
php artisan cache:clear   
````  
## TODO
Remplacement case de depart non possibles (fixé dans la map au depart)
Firefox bug sur l hover display none pour pousser un lemming
Websocket / Chat

## License

Cette application est open-source et utilise la licence [MIT].

## Thanks
Hexagon JS : https://github.com/flauwekeul/honeycomb
jQuery : https://jquery.com/
Resources map : https://gamedev.stackexchange.com/questions/6382/how-to-create-a-hexagon-world-map-in-php-from-a-database-for-a-browser-based-str
Emoji : https://emojicombos.com/animal
