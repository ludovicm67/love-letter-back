[![Build Status](https://travis-ci.com/ludovicm67/projet-ihm-back.svg?token=4LgtqqAc8ZPrPBEdLaF6&branch=master)](https://travis-ci.com/ludovicm67/projet-ihm-back)

# Projet IHM (partie backend)

Pour accéder à la partie frontend de l'application :
https://github.com/ludovicm67/projet-ihm-front

## Récupération des sources

Commencez tout d'abord par clôner le dépôt, et rendez-vous dans le dossier :

```sh
git clone git@github.com:ludovicm67/projet-ihm-back.git
cd projet-ihm-back
```

## Mise en route

Il existe deux moyens de lancer ce projet. Soit en utilisant un conteneur
Docker, qui s'assurera que le tout fonctionne et soit rapidement fonctionnel,
soit l'installation et la configuration manuelle de chaque élément, qui peut
s'avérer nettement plus fastidieuse.

### Lancer un conteneur Docker

#### Prérequis sous linux (Debian-based)

Installer `docker-ce` en se basant sur le guide suivant :
https://www.digitalocean.com/community/tutorials/how-to-install-and-use-docker-on-ubuntu-16-04

Installez ensuite `docker-compose`, avec la commande suivante :

```sh
sudo apt install docker-compose
```

Et normalement vous êtes prêts pour la suite !

#### Prérequis sous Windows

Installez par exemple simplement Docker Toolbox, en suivant les instructions
suivantes : https://docs.docker.com/toolbox/toolbox_install_windows/

En suivant les différentes étapes, vos devriez avoir les commandes
`docker` et `docker-compose` disponibles; c'est tout ce dont on aura besoin.

La Toolbox se lance dans une VM VirtualBox; il faudra donc penser à bien
ouvrir les ports nécessaire pour notre application; celà se fait de la manière
suivante depuis VirtualBox : clic droit sur la VM (appelée `default` par défaut)
et choississez `Configuration...`, puis, dans l'onglet `Réseau`, sur la
carte 1, vérifiez que le mode d'accès est bien défini sur `NAT` et cliquez
ensuite sur `Avancé`, puis sur `Redirection de ports`, et ajoutez les deux
règles suivantes :

Règle pour exposer l'API :
 - Nom: peut importe, par défaut : `Rule 1`
 - Protocole : `TCP`
 - IP hôte : laisser vide
 - Port hôte : `1337`
 - IP invité : laisser vide
 - Port invité : `1337`

Règle pour exposer la partie websockets :
 - Nom: peut importe, par défaut : `Rule 2`
 - Protocole : `TCP`
 - IP hôte : laisser vide
 - Port hôte : `1338`
 - IP invité : laisser vide
 - Port invité : `1338`

Et normalement, en lançant Docker, il ne devrait pas y avoir de soucis pour
passer à la suite.

#### Lancement du conteneur

Lancez simplement la commande suivante depuis un terminal de commandes :

```sh
docker-compose stop && docker-compose build && docker-compose rm -vf && docker-compose up
```

La commande en question va d'abord s'assurer que le conteneur a bien été stoppé,
puis va lancer un build, forcer la suppression des volumes précédents dans le
but de partir sur des bases saines et sûres, et puis va lancer le conteneur.

Le premier lancement pourra prendre quelques minutes, un peu moins de 5 minutes,
le temps que tout se mette en place correctement; mais les lancements suivants
seront nettements plus rapides, grâce au cache !

Le conteneur en question va lancer également en parallèle un conteneur avec
MySQL et un autre avec redis; le conteneur principal contient un Apache
avec un PHP >= 7, NodeJS et la commande pour lancer le serveur de websockets.

Pour tester si tout est opérationnel, il suffira de se rendre par exemple :
  - sur http://localhost:1337/ et voir si une page s'affiche effectivement, pour
    tester la partie API
  - sur http://localhost:1338/socket.io/socket.io.js et voir s'il y a bien une
    réponse.

Si dans les deux cas il y a une réponse, c'est que probablement tout est bon.

### Mise en route manuelle

#### Prérequis

Pour lancer le projet, il vous faut impérativement :
  - une version de PHP >= 7.0
  - `composer` (https://getcomposer.org/), pour l'installation des dépendences
  - MySQL de préférence, comme base de données
  - redis (peut se lancer aussi dans un conteneur Docker de la manière suivante:
    `docker run --name redis-server -d -p 6379:6379 redis`)
  - NodeJS, et installer globalement `laravel-echo-server` (se fait de la
    manière suivante : `sudo npm install -g laravel-echo-server`)

#### Installation

Créez une base de donnée MySQL spécifique pour le projet.

Copiez le fichier `.env.example` en `.env`, et modifiez les informations de
connexion à la base de données.

Installez les dépendences requises, avec la commande `composer install`.

Générez ensuite une clé d'application avec la commande
`php artisan key:generate`.

Générez ensuite une clé de sécurité pour les tokens JWT avec la commande :
`php artisan jwt:secret`.

Enfin, lancez les migrations et les seeds (pour créer et peupler la base de
données), avec la commande : `php artisan migrate:fresh --seed`.

Vérifiez que tout est en ordre de marche, en lançant l'une de ces commandes,
qui sont équivalentes, mais ne fonctionnent pas toutes en fonction du système :
 - `make tests`
 - `vendor/bin/phpunit`
 - `php vendor/bin/phpunit` (à condition d'avoir `php` dans son `PATH`)

#### Lancement

Lancez la partie API avec `php artisan serve`, qui expose par défaut sur
http://localhost:8000

Lancez la partie websocket avec : `laravel-echo-server start`, qui expose par
défaut sur http://localhost:3001/socket.io/socket.io.js

#### Mise à jour

Vous souhaitez récupérer une nouvelle version ?

Utilisez la commande `git pull`, puis faites un `composer install` à nouveau,
afin d'être certain d'avoir les bonnes dépendances.

## Les différentes routes de l'API

Les différentes routes de l’API sont les suivantes :

  - `POST: /api/register`, avec
    - `name`
    - `password`
  - `POST: /api/login`, avec
    - `name`
    - `password`
  - `GET: /api/logout?token=TOKEN`
  - `POST: /api/game/create?token=TOKEN`, avec :
    - `slot2`, un int entre 0 et 2
    - `slot3`, un int entre -1 et 2
    - `slot4`, un int entre -1 et 2
  - `GET: /api/game/list?token=TOKEN`
  - `GET: /api/game/waitlist?token=TOKEN`
  - `POST: /api/game/join?token=TOKEN`, avec
    - `game_id`
  - `POST: /api/game/play?token=TOKEN` , avec
    - `game_id`
    - `action` = `pick_card` ou `play_card`
    - `played_card`
    - `choosen_player`
    - `choosen_card_name`
  - `POST: /api/game/delete?token=TOKEN` , avec
    - `game_id`
  - `GET: /deleteallgames?token=TOKEN`

Pour les valeurs des différents slots :
  - Slot 1 = joueur1 = le créateur = forcément un joueur humain => pas besoin
    d'être renseigné, car inutile.n
  - Slot 2, 3 et 4 :
    - -1 = emplacement fermé
    - 0 = emplacement pour un joueur humain
    - 1 = IA facile
    - 2 = IA difficile

## Technologies utilisées

 - le framework PHP [Laravel](https://laravel.com/)
 - [Travis](https://travis-ci.com/) pour l'intégration continue
 - GitHub pour héberger les sources, `git` pour le versionning
 - redis
 - MySQL
 - websockets, à travers `laravel-echo-server` (NodeJS)
