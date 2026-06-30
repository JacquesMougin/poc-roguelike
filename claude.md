# Contexte du projet

## Objectif

Créer un POC de jeu coopératif tour par tour en utilisant principalement l'écosystème Symfony (Symfony UX, Mercure, Turbo, Stimulus), afin d'explorer les capacités de Symfony plutôt que de construire un jeu avec un moteur JS comme Phaser ou PixiJS.

Le but est de rester le plus "Symfony-centric" possible tout en limitant le JavaScript au strict nécessaire.

---

# Concept du jeu

* Une page desktop sert d'écran principal.
* Les joueurs rejoignent la partie via un QR Code depuis leur téléphone.
* Chaque joueur utilise son mobile comme contrôleur.
* Le desktop affiche le déroulement de la partie.

Le jeu est un roguelike coopératif **au tour par tour**, pas un jeu temps réel.

Le déroulement est basé sur des phases :

* Lobby
* Début de combat
* Choix des actions
* Résolution selon l'initiative
* Récompenses
* Salle suivante

Il n'y a pas de boucle temps réel permanente.

---

# Architecture

## Symfony

Symfony est la source de vérité.

Il gère :

* le GameState
* les règles du jeu
* les combats
* l'initiative
* le loot
* les changements de phase
* les validations

Le frontend ne décide jamais de la logique.

---

## Mercure

Mercure sert uniquement à diffuser des événements.

Il ne contient aucune logique métier.

Il ne modifie jamais le GameState.

Pattern :

Client → Symfony → GameState mis à jour → Mercure → Clients

Mercure agit comme un bus d'événements.

---

# Événements

Les événements doivent représenter des faits métier.

Exemple :

* PlayerJoined
* PlayerReady
* PlayerRenamed
* TurnStarted
* AttackResolved
* MonsterDied
* LootDropped

Le backend ne doit jamais envoyer des instructions d'interface comme :

* ShakeMonster
* MoveCard
* PlayAnimation

Le frontend décide lui-même des animations.

---

# Turbo Streams

Turbo Streams est très adapté pour les changements de structure de l'interface.

Exemples :

* ajout d'un joueur
* suppression d'un joueur
* changement du pseudo
* état prêt / non prêt
* changement d'écran
* écran de récompenses

Turbo Streams est utilisé avec Mercure grâce à :

```twig
{{ turbo_stream_listen('game/' ~ game.id ~ '/players') }}
```

Le desktop s'abonne à un topic Mercure et applique automatiquement les Turbo Streams reçus.

Pour le lobby, privilégier :

* append
* replace
* remove

Éviter de remplacer tout le conteneur lorsqu'une seule carte change.

---

# Stimulus

Stimulus est utilisé uniquement pour rendre l'interface vivante.

Il gère :

* animations
* transitions
* effets visuels
* séquences de combat

Exemple :

AttackResolved

↓

Stimulus joue :

* déplacement du joueur
* tremblement du monstre
* popup "-12"
* mise à jour des PV

Le backend n'a jamais connaissance de ces animations.

---

# Répartition des responsabilités

Symfony :

* logique métier
* GameState
* génération éventuelle de Turbo Streams

Mercure :

* diffusion des événements

Turbo Streams :

* modifications structurelles de l'interface

Stimulus :

* animations
* interactions visuelles
* réactions aux événements

---

# Lobby

Le lobby est un excellent candidat pour Turbo Streams.

Exemple :

* PlayerJoined
  → append d'une PlayerCard

* PlayerRenamed
  → replace de la PlayerCard

* PlayerReady
  → replace de la PlayerCard

Aucun JavaScript spécifique n'est nécessaire.

---

# Combat

Le combat est piloté par des événements métier.

Exemple :

AttackResolved

Le frontend interprète cet événement et joue les animations nécessaires.

Il ne faut pas envoyer plusieurs événements uniquement pour piloter l'interface.

Préférer un seul événement métier décrivant ce qui s'est produit.

---

# Philosophie générale

Turbo Streams est utilisé lorsque le serveur doit reconstruire une partie de l'interface.

Stimulus est utilisé lorsque l'interface doit devenir interactive ou animée.

Mercure synchronise les clients.

Symfony reste toujours la source de vérité.

Le projet cherche à explorer les capacités de Symfony UX sans tomber dans une SPA complète.
