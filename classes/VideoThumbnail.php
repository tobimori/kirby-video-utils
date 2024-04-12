<?php

namespace tobimori\VideoUtils;

use Kirby\Cms\File;

class VideoThumbnail extends File
{
	/**
	 * Disable UUIDs to avoid creating a new content file
	 */
	public function uuid(): null
	{
		return null;
	}
}
