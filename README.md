[![Build Status](https://travis-ci.com/ludovicm67/projet-ihm-back.svg?token=4LgtqqAc8ZPrPBEdLaF6&branch=master)](https://travis-ci.com/ludovicm67/projet-ihm-back)

# Projet IHM (partie backend)

Pour accéder à la partie frontend de l'application :
https://github.com/ludovicm67/projet-ihm-front

## Mise en route

### Prérequis

Pour lancer le projet, il vous faut impérativement :
 - une version de PHP >= 7.0
 - `mysql` de préférence
 - `composer` (https://getcomposer.org/), pour l'installation des dépendences

### Installation

Commencez tout d'abord par clôner le dépôt, et rendez-vous dans le dossier :

```sh
git clone git@github.com:ludovicm67/projet-ihm-back.git
cd projet-ihm-back
```

Installez les dépendences requises, avec la commande `composer install`.

Copiez le fichier `.env.example` en `.env`, et modifiez les informations de
connexion à la base de données.

Générez ensuite une clé d'application avec la commande
`php artisan key:generate`.

### Mise à jour

Vous souhaitez récupérer une nouvelle version ?

Utilisez la commande `git pull`, puis faites un `composer install` à nouveau,
afin d'être certain d'avoir les bonnes dépendances.

## Technologies utilisées

 - le framework PHP [Laravel](https://laravel.com/)
 - [Travis](https://travis-ci.com/) pour l'intégration continue
