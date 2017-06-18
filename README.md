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

Hormones converts organ names into internal organ IDs, which ranges from 0 to 63. Therefore, there must not be more than
64 organs in a network. This is a technical constraint that cannot be fixed easily. The organ names and IDs are listed
in the `hormones_organs` table.

If there are too many organs in the `hormones_organs` table, those that are not not used in any tissues (previously used
on a tissue but later changed to another organ name) will be deleted to free up organ IDs. Hormones may fail to enable
if no organ IDs are available; you may then want to delete the organ IDs manually by editing the `hormones_organs`
table. Note that organ IDs should not be changed carelessly since they are referenced from other places in the database too and the changes may not be easily cascaded.

##### Tissue
Similarly, a tissue is a component of an organ, i.e. one PocketMine server instance. In Hormones, a "tissue" always means "a PocketMine server", since the word "server" is ambiguous.

Hormones lists all tissues in the `hormones_tissues` table. Tissues are identified by a generated tissue ID; the `localize`.`name` value is not used for identifying tissues directly nor indirectly, but only for your convenience.

##### Hormone
A hormone is a signal that would be received by all tissues (or all tissues in certain organ(s)). It triggers certain
actions on tissues based on the hormone type. For example, the StopServerHormone will cause the receiving tissues to
shut down (and restart depending on the options starting the server).

When a hormone is released, it is inserted as a row in the `hormones_blood` table. It usually takes up to one second or
several for all online tissues to download the new hormone, so it's inappropriate to delete the hormone immediately
after it's inserted.

###### Lingering hormone
There are also some "lingering" hormones, which have a longer lifetime. Lingering hormones would be handled every time a
tissue starts until they have expired. (Non-lingering hormones expire immediately after insertion)

##### Kidney
The kidney is a tool to clean up the database to delete expired hormones (at least several seconds or minutes after
they expire) periodically. Plugin developers working on Hormones-related plugins may want to disable it for debugging.
Other users should leave the relevant settings as default.

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

The `localize` section in config.yml would be most different for each server. You have to set the server organ name in
`localize`.`organ`, and set the player-visible server address in `localize`.`address`. Players will be transferred to
the tissue using the `localize`.`address` in that tissue as the server address (the port is detected from
server.properties, so no need to set it here), so don't use an internal IP address here (Hormones will raise a warning
if you do so). Here, you may want to use a user-friendly domain name instead of the raw IP address (the numbers).

Note that the server address is only visible to users under certain circumstances (e.g. using the /servers command), and
you can still disallow this by managing the permissions. However, note that the server addresses must be sent to the
client when they are being transferred, and this may become visible to users through client mods, so don't rely on
such permissions. To prevent players from exploiting this, you may have a look at [transferOnly](#transferonly).

The `kidney` section is very technical. Leaving its settings as default is already OK.

For other config values, they are related to more specific Hormones features, which will be explained in the [Features](#features) section below.

### Updating database
Hormones almost does all the database setup for you. Apart from creating a schema for Hormones to
connect to, you don't even need to connect to MySQL manually.

After updating Hormones by replacing the phar file in the plugins folder, if something in the database needs to be
changed, Hormones will detect it automatically. If this is a backwards-incompatible update, i.e. older versions of
Hormones cannot work together with the updated database, they will automatically shutdown.

## Features
### Load balancing
#### Full transfer
Since all tissues in the same organ should have the same function in the network, it is reasonable that a player gets
transferred to any other tissue in the network. Preferrably, it is the tissue with the most empty slots (or the server
with the lowest % online players). Set the `balancer`.`enabled` in config.yml to `true`, and when the number of players
on the server reach the limit in `balancer`.`playerSoftLimit`, players trying to join the server will be transferred to
the most empty tissue in the organ. If all tissues in the organ are currently full, the player will be kicked.

Players whose name is listed in `balancer`.`fullExemptPlayers` will be exempted from this kind of transfer. You may want to
put the names of your server ops here.

Server-full transfer does not do anything with the `max-players` option in server.properties. If you want to allow the
exempted players join the server,

###### FAQ: Can I integrate exemptPlayers with permissions?
It is not practical. Full-transfer is executed when the player just connected, even before permission manager plugins
like PurePerms setup the player's permissions. Players must be transferred as soon as possible to prevent sending
unnecessary data to the player, which contradicts with the initiatives of "load balancing".

#### Always transfer
In the scenario in the Introduction above, I mentioned an "entry" tissue. It's the server address visible to the players
such that players join this "entry" tissue every time they enter your network. However, if players stay online on the
entry tissue, it will be overused because it is responsible for both handling online players and redirecting new
player's traffic. So instead, no players will be allowed to stay on the "entry" tissue, and they will always be
transferred to one of the tissues in the "lobby" organ.

This can be accomplished by always-transfer. The `balancer`.`alwaysTransfer` in config.yml can be set to an organ name
such that players will always be transferred to that organ. Apart from putting an explicit organ name, you can also put
`"last"`. Hormones will then transfer the player to the organ of the last tissue (with `balancer`.`logLast` enabled) the
player has joined.

If all tissues in the destination organ are full, or if it is `"last"` but the player has never joined any tissues with
`balancer`.`logLast` enabled, Hormones will attempt to transfer the player to the organ specified in
`balancer`.`alwaysFallback` instead (if it is not `false`).

If `balancer`.`alwaysFallback` is `false`, or all tissues in the `balancer`.`alwaysFallback` organ are full, the player
will be kicked if `balancer`.`alwaysKick` is enabled, or stay on the current tissue otherwise.

Players in the `balancer`.`alwaysExemptPlayers` list will never be transferred due to always-transfer.

<!--
TODO finish an example flowchart:

```
Player -----[if balancer.alwaysTransfer]---- > Are there available --+--[Yes]---- > Transfer to a lobby tissue
join     |  [ is "lobby"               ]        lobby tissues?       +  [No]----- >
         |
         +--[if balancer.alwaysTransfer is "last"]---- > Did player join the -----[Yes, last organ]---- > Are there available
         |                                               network before?          [was skyblock   ]       skyblock tissues?
```
-->

#### Stop transfer
When a tissue stops, transfer all online players to other tissues in the organ.

If there are not enough slots in the organ to transfer players to, they will be kicked due to server stop. Players who
logged in more recently have higher chance to get transferred instead of getting kicked.

<!-- TODO feature: exempt internally transferred players -->

#### Organic transfer
Conventionally, players are transferred to another server upon actions like typing a command, clicking on a sign, etc.
to enter another part of the network, e.g. hub server :arrow_right: a skyblock server. However, this is inefficient in
a system like Hormones, because some servers might easily get overcrowded.

Hormones provides a command interface for transferring to the most empty tissue in an organ. This is controlled by the
`organicTransfer`.`mode` option in config.yml. If it is set to `group`, the `/ot` (`/organic-transfer`) command will be
registered, accepting an organ name as the argument. For example, running the command `/ot lobby` will transfer yourself
to the `lobby` organ. If `organicTransfer`.`mode` is set to `direct`, the organ name will be the command name. For
example, running `/lobby` will transfer yourself to the lobby.

You can also execute the commands to transfer other players. If `organicTransfer`.`mode` is set to `group`, put the
player name before the organ name, e.g. `/ot Steve lobby`. If it is set to `direct`, simply put the player name as
the argument, e.g. `/lobby Steve`.

This command would be very useful when used together with [TapToDo](https://poggit.pmmp.io/p/TapToDo).

#### Summed player count
In the MCPE server list screen, the number of online players and slots for each server is shown. With Hormones, you may
change this value to show the total in the organ, or the total in the network. You can customize this through changing
the `balancer`.`queryPlayerCount` to `tissue` (default), `organ` (total in the organ) or `network` (total in the
network).

### Administration
#### Broadcasting
You can broadcast a message in all tissues in the network or the current organ using the /nsay and /osay commands
respectively.

#### Restarting network
You can restart all tissues in the network with `/stop-all` (or `/nstop`).

#### Tissue list
The `/tissues` (or `/servers`) command lists all online tissues, groups them by organs and shows their online status.

#### Hormones status
The `/hormones` command shows the network status and stats for nerds of a tissue.

### Moderation
Hormones hosts a shared mute/ban list on the MySQL database using lingering hormones. Such records are permanently
deleted after the expiration of the mute/ban period.

Players who are muted cannot chat normally. Using commands, including the /tell command (if they have such permission),
is not affected.

Players who are banned cannot join the server. However, if the `moderation`.`banTransfer` is set to a string (in YAML,
`false` is a keyword and is not a string; if you want to make sure it's a string, enclose the text with double quotes
`""`), banned players will be transferred to the organ named in the `moderation`.`banTransfer` option.

Mutes and bans identify players with the case-insensitive name and the current IP of the player at the moment they are
banned/muted. If players with the same case-insensitive name or the same IP join the network again, they are also
banned/muted.

Hormones supports "sectional moderators" &mdash; If a player only has the permission nodes for sectional moderation but
not that for global moderation, the bans/mutes issued by that moderator is only effective in the organ of the tissue
where the command was executed.

<!--
* Single-session control
* Transfer whitelist
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

There are also transferring commands defined according to `organicTransfer.mode` in config.yml. Refer to the [Organic Transfer](#organic-transfer) section above.

##### Remarks
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
| s | Second | one system clock second |
| sec | Second | one system clock second |
| second | Second | one system clock second |

Don't ask me why I put "millennium" there. Some judges have a strange sense of favour of imprisoning criminals for 300
years rather than life imprisonment.

## Running this plugin
Use the latest version from [Poggit](https://poggit.pmmp.io/p/Hormones).

Development builds can be found from [Poggit-CI](https://poggit.pmmp.io/ci/LegendOfMCPE/Hormones/~).

This plugin uses the Poggit virion system. To run this plugin from source for testing, provide the
[required virions](#third-party-software-used) in the runtime using instructions from
[the virion framework documentation](https://github.com/poggit/support/blob/master/virion.md).

## Third-party software used
* This plugin uses the library [libasynql](https://github.com/poggit/libasynql) by @poggit.
* This plugin uses the library [spoondetector](https://github.com/Falkirks/spoondetector) by @Falkirks.

## Contact
Shall you need a human, join our Gitter chat. [![Join the chat at https://gitter.im/LegendOfMCPE/Hormones](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/LegendOfMCPE/Hormones?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)

If you have found a bug, please [create an issue on GitHub](https://github.com/LegendOfMCPE/Hormones/issues). make sure
not to submit duplicate bugs.
