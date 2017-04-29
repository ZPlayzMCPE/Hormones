Framework Terminology
===
> The terminology for users trying to master Hormones

Hormones is a plugin made for optimizing server networks. A server network is like a large _organism_, with different _tissues_, belonging to different _organs_, connected by the circulatory system (_blood_). The _blood_ contains _hormones_, responsible for transmitting different signals between tissues and organs.

There are different types of hormones, carrying different signals. Some hormones are only effective on certain organs, and tissues of other organs should ignore them.

Sometimes tissues are dead, and they get cleaned by _phagocytes_. Hormones provides a [`phagocyte.php`](phagocyte.php) script that kills servers when they become offline for a long time.

> Direct nervous system (direct connections between servers) is likely to produce a _vagus nerve_ (servers connecting wrongly), so I'm not gonna create it in this plugin :laughing:

Code terminology
===
> The terminology for people trying to contribute to this project

Hormones are brought to tissues through _arteries_, so the task for downloading data to the server is called `Artery`.

On the other hand, to send hormones to the circulatory system, hormones enter the circulatory system through _veins_, so the task for uploading data to the network is called `Vein`.

There are also _lymph_ vessels to return materials to the circulatory system, but it's less rapid. In our case, we need to update the central database about the status of each server, which does not need to be reported rapidly. 

After a while, the hormones have circulated through the organism, and they are no longer needed. They are broken down by the _liver_ into urea and extracted from blood by the _kidney_. In our case, liver and kidney have similar function, so they are generalized as the `Kidney`.
