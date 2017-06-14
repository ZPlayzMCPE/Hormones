# Hormones [![Join the chat at https://gitter.im/LegendOfMCPE/Hormones](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/LegendOfMCPE/Hormones?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge) [![Poggit-CI](https://poggit.pmmp.io/ci.shield/LegendOfMCPE/Hormones/~?style=square)](https://poggit.pmmp.io/ci/LegendOfMCPE/Hormones/~)

Ultimate endocrine management for load balancing and network administration

## Introduction
Hormones is a plugin designed for server networks with many servers. You may have a network like this:

```
entry: play.myserver.com:19132 with 0 slots
lobby #1: lobby.myserver.com:19132 with 20 slots
lobby #2: lobby.myserver.com:19133 with 20 slots
lobby #3: lobby2.myserver.com:19132 with 10 slots
lobby #4: lobby2.myserver.com:19133 with 10 slots
hunger games #1: hg.myserver.com:19132 with 24 slots
hunger games #2: hg.myserver.com:19133 with 24 slots
hunger games #4: hg2.myserver.com:19132 with 24 slots
skyblock #1: sb.myserver.com:19132 with 15 slots
skyblock #2: sb2.myserver.com:19132 with 15 slots
etc...
```

You may want your network to be like this &mdash; Players can join at play.myserver.com, and
they are automatically redirected to one of the lobby servers, and they can join the game servers using portals,
commands or whatever in the lobby, and you want all lobby and game servers to be load-balanced such that you can have an
equal amount of players on each server of the same type.

You may even want more convenient control over the whole network &mdash; When you ban a player on any servers, you may
want to have him banned on all servers in your network; when a lobby server is full, you may want to transfer the player
to another lobby server with more slots; you may not want to let a player join any of your servers without being
transferred from the entry server to prevent overcrowding...

*If you are a plugin developer: You may even want to transfer data across servers in the network conveniently &mdash; If
you are working on a team plugin, you may want to allow players on different servers to be able to talk on the team chat
together; if you are working on a hardcore plugin, a player died on one of the servers, you may want to clear his
inventory on all servers...*

If these are what you want to do, Hormones is the perfect plugin designed for you. Hormones is a plugin for managing
different servers in your network. *For developers: Hormones also has a "Hormone API", which allows plugins to
propagate data to other servers conveniently through objects called "Hormone".*

Hormones can connect all your servers, providing convenience in both administration and moderation, with a single MySQL
schema. Give Hormones a MySQL database where tables can be freely created and edited, and with simple setup, Hormones
will link different servers into server types and into your big network, just like the human coordinate system linking
different body tissues into different organs, hence into the whole human body.

## Some special terms
##### Organ
An organ is a group of servers which have the same type, serving the same function in your server. For example, in the
example in the introduction above, there are four organs, namely `entry`, `lobby`, `hunger_games` and `skyblock`. Organ
names are taken as command arguments, so it must not contain spaces and should be easy to type for players. They can
also be made command names optionally.

Organ names are case-insensitive, i.e. servers are in the same organ as long as their organ names are the same,
regardless whether they are in upper case or lower case, etc.

##### Tissue
Similarly, a tissue is a component of an organ, i.e. one PocketMine server instance.

## Features
#### Load balancing

## Setup
Install a MySQL database that can be accessed from all your servers. Create a user for Hormones (e.g. `'hormones'@'%'`),
and create a schema (e.g. `hormones`). Grant access (at least requires LOCK, CREATE TABLE, CREATE TRIGGER, SELECT,
INSERT, UPDATE and DELETE) on the new schema to the Hormones user.

Now, for each of the servers in your network, [install Hormones](https://poggit.pmmp.io/p/Hormones) and run the server
once. Hormones will be automatically disabled if it's run the first time, but it will generate a config.yml file at
`plugins/Hormones/config.yml`. Edit this file to setup Hormones for each server.

First, delete this line from the config.yml:

```
Dear User: Please delete this line after you have finished setting up the config file.
```

Hormones won't run if this line is present. Next, put the MySQL login for the Hormones user you just created in the
`mysql` section. _Using a separate user just for the Hormones' use is recommended_ to enhance security. Leave `socket`
empty (as `""`) if you don't know what it is.

## Features
* Network administration / moderation
    * Check the status of the network using /hormones
    * List all online servers using /servers
    * Stop all online servers using /nstop
    * Broadcast a message to all servers in the _network_ using /nsay
    * Broadcast a message to all servers in the _organ_ using /osay
    * Mute players of a certain name/from a certain IP in the network/tissue using /nmute
    * Ban players of a certain name/from a certain IP in the network/tissue using /nban
* Load balancing
    * When a player joins a full server, he would be transferred to another server of the same type.
    * Transfer to the most available server of a given organ using /{organ name}

<!--
* Single-session control
* Transfer whitelist
* NetChat
-->

## Command usage
The following table lists the commands available with Hormones. The remarks below the table show the specific meaning of
certain arguments. Arguments ending with `...` means that it can contain spaces. Arguments bracketed with `<` `>` are
required, while arguments bracketed with `[` `]` are optional. 

| Command | Description | Usage |
| :-----: | :---------: | :---: |
| /servers | List all online servers. | `/servers`, or `/tissues` |
| /hormones | Show Hormones version and status, e.g. connection speed, players in network, etc. | `/hormones` |
| /stop-all | Stop all servers in the _network_ (including those in other organs). If the servers were started with looping (`./start.sh -l`), they may restart. | `/stop-all`, or `/nstop` |
| /nban | Ban a player from the network or current organ, depending on the issuer's permission | `/nban <player> <duration> [message ...]`  |
| /nmute | Mute a player in the network or current organ, depending on the issuer's permission | `/nmute <player> <duration> [message ...]`  |
| /nsay | Broadcast a message to all players, or players with a certain permission, in the network | `/nsay [perm] <message ...>` |
| /osay | Broadcast a message to all players, or players with a certain permission, in the organ | `/osay [perm] <message ...>` |

If `organicTransfer.mode` in config.yml is set to `direct`, There are also an arbitrary number of commands for
transferring to other organs. For example, if you have an organ called `pvp`, players can type the `/pvp` command to
transfer to the most empty `pvp` server. If the command `/pvp` is registered by another plugin, players can type
`/organ:pvp` instead.

If `organicTransfer.mode` is set to `off`, no such commands will be registered.

If `organicTransfer.mode` is set to `group`, a command `/organic-transfer` (alias: /ot) will be registered. If you have
an organ called `pvp`, players can type `/ot pvp` to transfer to the most empty `pvp` server.

### Remarks
`<player>` refers to the name of an _online_ player. It is case-insensitive. If you only type the first few characters
of the player's name, the online player with the shortest name starting with these characters will be chosen.

`[perm]` in `/nsay` and `/osay` is in the format `perm:<permission>`, where `<permission>` is the permission node. Only
players with the permission node will receive the broadcast. If `[perm]` is not provided, all players will receive it.

`<duration>` refers to a period of time. You may type a number directly, which will be in minutes. You may also type
with units, which may be in several groups of `<coefficient> <unit>` that will be added up. For example, `1d12hr` refers
to "1 day and 12 hours", and `5min10min` refers to "5 minutes and 10 minutes", i.e. 15 minutes. The units are
case-insensitive. Characters other than a-z, 0-9 and `.` are ignored. The following units are accepted:

| unit | in English | equivalent to |
| :----: | :--------: | :-----------: |
| millennium | Millennium | 365242 days |
| mm | Millennium | 365242 days |
| century | Century | 36524 days |
| decade | Decade | 3652 days |
| y | Year | 365 days |
| yr | Year | 365 days |
| year | Year | 365 days |
| season | Season | 91 days |
| month | Month | 30 days |
| fortnight | Fortnight | 14 days |
| w | Week | 7 days |
| wk | Week | 7 days |
| week | Week | 7 days |
| d | Day | 86400 seconds |
| day | Day | 86400 seconds |
| h | Hour | 3600 seconds |
| hr | Hour | 3600 seconds |
| hour | Hour | 3600 seconds |
| m | Minute | 60 seconds |
| min | Minute | 60 seconds |
| minute | Minute | 60 seconds |
| s | Second | (common sense) |
| sec | Second | (common sense) |
| second | Second | (common sense) |

Don't ask me why I put "millennium" there. Some judges have a strange sense of favour of imprisoning criminals for 300
years rather than life imprisonment.

## Running this plugin
Please use the latest build from [Poggit-CI](https://poggit.pmmp.io/ci/LegendOfMCPE/Hormones/~).

This plugin uses the Poggit virion system. To run this plugin from source for testing, provide the required virions in
the runtime using instructions from [the virion framework documentation](https://github.com/poggit/support/blob/master/virion.md).

## Third-party software used
* This plugin uses the library [libasynql](https://github.com/poggit/libasynql) by @poggit.
* This plugin uses the library [spoondetector](https://github.com/Falkirks/spoondetector) by @Falkirks.
