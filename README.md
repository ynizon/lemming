<h1>Lemming</h1> 

## A propos

Cette application a pour but de recréer le jeu Lemming.
https://www.trictrac.net/jeu-de-societe/lemming
Ce n'est pas fini !

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
npm install
npm run dev
````  
## TODO
Deplacement souris adverses
Bug non perte opacity lors du 1er deplacement d'un lemming
Remplacement case de depart non possibles (fixé dans la map au depart)
Websocket / Chat

## License

Cette application est open-source et utilise la licence [MIT].

## Thanks
Hexagon JS : https://github.com/flauwekeul/honeycomb
Laravel Echo : https://grafikart.fr/tutoriels/messagerie-echo-977
Resources map : https://gamedev.stackexchange.com/questions/6382/how-to-create-a-hexagon-world-map-in-php-from-a-database-for-a-browser-based-str
Emoji : //https://emojicombos.com/animal
