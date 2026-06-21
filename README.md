# Générateur d'attestations d'hébergement

## Installation

```bash
yunohost app install https://github.com/selfhoster1312/attestation_ynh
```

## Configuration

Le fichier de configuration se trouve dans `/var/www/attestation/config.php`:

```php
<?php
$admins = [ ];
$lieux = [
    "élysée" => "Élysée"
];
```

Pour ajouter/enlever un template, il faut modifier la variable `$lieux`.

### Récupérer automatiquement des factures

Un script et un timer systemd `attestation-facture-fdn` sont fournis pour se connecter
automatiquement à son compte client FDN et récupérer la dernière facture, pour la déposer
dans les fichiers associés au template de son choix.

Il faut donc d'abord activer le timer avec `systemctl enable attestation-facture-fdn.timer`.

Ensuite, il faut éditer le fichier de configuration `/var/www/attestation/facture-fdn.env` pour renseigner
ses identifiants de connexion à l'interface client, ainsi que le fichier de destination quand une nouvelle
facture est téléchargée. Il suffit de placer la facture dans le dossier `extra/` du template pour qu'elle
soit rajoutée comme page additionnelle de l'attestation.

Exemple de configuration:

```
FDN_LOGIN="mylogin@fdn.ilf.kosc"
FDN_PASSWORD="acab"
FDN_DEST="/var/www/attestation/templates/élysée/extra/fdn.pdf"
```

## Administrateurices

La liste des `$admins` définies dans le fichier de config détermine les utilisateurices
qui ont accès aux dernières attestations crées, pour vérifier qu'il n'y a pas des robots
qui font n'importe quoi avec un compte.

## Rétention des données

Toutes les attestations d'hébergement sont supprimées automatiquement après 7 jours,
afin d'éviter de conserver des données sensibles. C'est assuré par l'installation d'un
timer/service systemd qui lance tous les jours la commande:

```bash
find /var/www/attestation/www/output -mtime +7 -type f -delete
```

## Créer son propre template

Un template d'exemple est fourni, mais ce logiciel n'est utile que si on fabrique son template d'attestation d'hébergement.

## License

GNU GPLv3.

Un exemple de template est fourni. Pour rajouter un template, il faut
éditer /var/www/attestation/config.php.

Ensuite, il faut éditer 
