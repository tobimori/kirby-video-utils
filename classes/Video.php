<?php

namespace tobimori\VideoUtils;

use FFMpeg\FFMpeg;
use FFMpeg\FFProbe;
use Kirby\Cms\File;

/**
 * The Video class enriches the File class with video-specific methods
 */
class Video extends File
{
	/**
	 * Returns the FFMpeg instance
	 */
	protected function ffmpeg(): FFMpeg
	{
		return FFMpeg::create();
	}

	/**
	 * Returns the FFProbe instance
	 */
	protected function ffprobe(): FFProbe
	{
		return $this->ffmpeg()->getFFProbe();
	}

	/**
	 * Creates a new Video object
	 */
	public function __construct(array $props)
	{
		parent::__construct($props);
	}

	/**
	 * The dimensions of the video
	 */
	protected $dimensions;

	/**
	 * Returns the dimensions of the video
	 */
	public function dimensions(): array
	{
		if (isset($this->dimensions)) {
			return $this->dimensions;
		}

		$dimensions = $this->ffprobe()->streams($this->root())->videos()->first()->getDimensions();

		return $this->dimensions = [
			'width' => $dimensions->getWidth(),
			'height' => $dimensions->getHeight()
		];
	}

	/**
	 * The ratio of the video
	 */
	public function ratio(): float
	{
		$dimensions = $this->dimensions();

		return $dimensions['width'] / $dimensions['height'];
	}

	/**
	 * Create a Video class from a File object
	 */
	public static function from(File $file)
	{
		return new static([
			'filename' => $file->filename(),
			'parent' => $file->parent(),
			'root' => $file->root(),
			'template' => $file->template(),
			'url' => $file->url()
		]);
	}
}
