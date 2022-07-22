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
retrouver ou on met la class x-1_y-1 pour le remove position au depart
Deplacement souris adverses
Meilleur lib hexagones (rotation 90) https://github.com/flauwekeul/honeycomb#grid
Impossible de traverser la case hexa85 apres le demarrage en 69
Remplacement case de depart non possibles
Websocket / Chat


## License

Cette application est open-source et utilise la licence [MIT].


## Thanks
https://grafikart.fr/tutoriels/messagerie-echo-977
