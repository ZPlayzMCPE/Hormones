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

class ChanRequiredPerms{
	const MIN_PERM_CHSUB = ChanPermLevel::QUIET;
	const MIN_PERM_LIST = ChanPermLevel::SUBSCRIBER;
	const MIN_PERM_MESSAGE = ChanPermLevel::SUBSCRIBER;
	const MIN_PERM_KICK = ChanPermLevel::MODERATOR;
	const MIN_PERM_MUTE = ChanPermLevel::MODERATOR;
	const MIN_PERM_BAN = ChanPermLevel::MODERATOR;
	const MIN_PERM_DELETE = ChanPermLevel::ADMIN;
}
