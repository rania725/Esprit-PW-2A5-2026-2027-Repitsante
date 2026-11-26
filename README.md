# Répit Santé

Répit Santé est un projet étudiant qui vise à coordonner les examens et les consultations afin de réduire les déplacements des patients vers Tunis. Le projet comprend le module **Utilisateurs & Accès**, une messagerie privée et les templates Front Office et Back Office. Les rendez-vous, parcours et alertes du tableau de bord restent des exemples fictifs de la maquette.

## Installation avec XAMPP

1. Place le dossier `projetweb` dans `C:\xampp\htdocs\`. Si tu as déjà créé ce dossier, copie les fichiers de l’archive à l’intérieur. Vérifie que `index.php` se trouve directement dans `C:\xampp\htdocs\projetweb\`.
2. Dans le panneau XAMPP, démarre **Apache** et **MySQL**.
3. Ouvre [phpMyAdmin](http://localhost/phpmyadmin/). Clique sur **Importer**, sélectionne `SQL/repit_sante.sql`, puis clique sur **Importer** ou **Exécuter** au bas de la page. Le script crée la base `repit_sante`, la table `users` et les tables de messagerie, avec les champs facultatifs de profil.
4. Ouvre [Répit Santé](http://localhost/projetweb/) et clique sur **Créer un compte**.

L’application utilise PHP 8.1+ et l’extension PDO MySQL. La connexion locale par défaut utilise `127.0.0.1:3306`, l’utilisateur `root` et le mot de passe MySQL vide de XAMPP. Si ta configuration diffère, renseigne les variables `REPIT_DB_HOST`, `REPIT_DB_PORT`, `REPIT_DB_NAME`, `REPIT_DB_USER` et `REPIT_DB_PASSWORD` selon tes paramètres. Pour un test avec un XAMPP personnalisé, tu peux aussi ajuster les valeurs locales dans `config/database.php`.

Si la base et la table `users` existent déjà, importe `SQL/messaging_migration.sql` et `SQL/profile_migration.sql` depuis phpMyAdmin. Ces migrations ajoutent les tables de messagerie et les champs téléphone/photo sans supprimer les comptes ni leurs données existantes; elles peuvent être réexécutées.

## Préparer le premier compte admin de test

1. Inscris-toi sur le site avec l’adresse de test de ton choix.
2. Dans ton phpMyAdmin local, clique à gauche sur **repit_sante** puis sur **users**. Dans l’onglet **Parcourir** (ou **Browse**), repère le compte grâce à son adresse e-mail et clique sur **Modifier**.
3. Dans la colonne `role`, choisis **admin**, puis clique sur **Exécuter**. Garde le mot de passe défini à l’inscription.
4. Connecte-toi avec ce compte sur le site. Le Back Office s’ouvre. Clique sur **Utilisateurs** dans le menu de gauche.
5. Crée un deuxième compte avec le rôle **Utilisateur** pour tester le Front Office et le refus d’accès au Back Office.

## Démonstration

| Action | Résultat attendu |
|---|---|
| Se connecter avec le compte normal | Le Front Office affiche « Mon espace ». |
| Modifier le téléphone ou ajouter/supprimer sa photo dans **Mon profil** | Les informations facultatives sont enregistrées et restent privées au compte connecté. |
| Ouvrir [Mes messages](http://localhost/projetweb/index.php?action=messages) | La liste des conversations du compte s’affiche. |
| Cliquer sur **Nouveau message**, choisir un destinataire et envoyer | La conversation est enregistrée et s’ouvre. |
| Utiliser la barre de recherche des messages | Les conversations sont filtrées par nom du participant ou contenu du dernier message. |
| Ouvrir une conversation avec un autre compte | Son historique s’affiche; les messages sont marqués comme lus. |
| Ouvrir [l’adresse du Back Office](http://localhost/projetweb/index.php?action=admin) avec le compte normal | Le serveur répond « Accès refusé ». |
| Se connecter avec le compte admin puis ouvrir **Utilisateurs** | La liste affiche les comptes enregistrés en base. |
| Ajouter un compte de test, le modifier et le supprimer | La liste reflète chaque opération. |
| Saisir un e-mail invalide ou déjà utilisé | Le formulaire affiche une erreur. |
| Saisir un mot de passe de connexion incorrect | La connexion est refusée. |
| Se déconnecter puis rouvrir la page protégée | L’application redirige vers la connexion. |

## Organisation MVC du workshop

- `Models/UserModel.php` représente un utilisateur et expose ses informations.
- `Controller/AuthController.php` gère l’inscription, la connexion, les rôles, la validation et le CRUD en base.
- `Controller/DashboardController.php` fournit les exemples du tableau de bord.
- `Controller/MessagingController.php` et `Models/MessagingModel.php` gèrent les conversations privées et les messages.
- Le profil utilisateur accepte un numéro facultatif et une photo JPEG, PNG ou WebP de 2 Mo maximum. Les photos sont servies par une route authentifiée et ne sont pas accessibles directement dans le dossier d’upload.
- `Views/front` contient les pages publiques, l’espace utilisateur et la messagerie; `Views/assets` contient les images, feuilles de style et scripts. Les URL d’assets tiennent compte du sous-dossier `projetweb`.
- `Views/admin` contient le Back Office et la gestion des comptes, avec un layout distinct.
- `index.php` initialise la session, fixe les en-têtes HTTP et dirige les requêtes vers les contrôleurs.

Le projet utilise des requêtes PDO préparées, `password_hash()` et `password_verify()`, un jeton CSRF pour les formulaires POST et `htmlspecialchars()` à l’affichage. La connexion renouvelle l’identifiant de session. Après 30 minutes d’inactivité, l’utilisateur revient à la connexion. Les droits d’accès sont vérifiés côté serveur sur les pages et actions administrateur.
