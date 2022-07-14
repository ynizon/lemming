<h1>Lemming</h1> 

## A propos

Cette application a pour but de recréer le jeu Lemming.
https://www.trictrac.net/jeu-de-societe/lemming
Ce n'est pas fini !

## Installation

- Remplir le fichier .env
- Créér la base de données
- Lancer les commandes:
  
        composer install    
        php artisan migrate --seed
        php artisan config:clear
        php artisan cache:clear   
        npm install
        npm run dev

## License

Cette application est open-source et utilise la licence [MIT].
