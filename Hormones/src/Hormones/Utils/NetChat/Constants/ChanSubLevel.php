<?php

/*
 *
 * Hormones
 *
 * Copyright (C) 2017 SOFe
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
*/

namespace Hormones\Utils\NetChat\Constants;

class ChanSubLevel{
	const VERBOSE = 0;    // including online/offline messages
	const NORMAL = 1;     // normal chat messages
	const MENTION = 2;    // chat messages mentioning subscriber
	const IGNORING = 3;   // ignores all messages from this channel
}
