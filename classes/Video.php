<?php

namespace tobimori\VideoUtils;

use FFMpeg\Coordinate\TimeCode;
use FFMpeg\FFMpeg;
use FFMpeg\FFProbe;
use FFMpeg\Format\Video\DefaultVideo;
use FFMpeg\Format\Video\X264;
use FFMpeg\Media\Video as FFMpegVideo;
use Kirby\Cms\File;
use Kirby\Filesystem\Dir;
use Kirby\Filesystem\F;
use Kirby\Uuid\Uuid;

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

	protected function openVideo(): FFMpegVideo
	{
		return $this->ffmpeg()->open($this->root());
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
	 * A thumbnail from the first second of the video
	 */
	public function thumbnail(): VideoThumbnail
	{
		$thumbnail = new VideoThumbnail([
			'filename' => F::filename($path = $this->root() . '.jpg'),
			'parent' => $this->parent(),
			'root' => $path,
		]);

		if ($thumbnail->exists()) {
			return $thumbnail;
		}

		$this->openVideo()->frame(TimeCode::fromSeconds(1))->save($path);
		return $thumbnail;
	}

	/**
	 * Transcode the video to a different format
	 */
	public function transcodeTo(string $format = null, string $ext = 'mp4'): static
	{
		$transcode = new static([
			'filename' => F::filename($path = "{$this->root()}.{$ext}"),
			'parent' => $this->parent(),
			'root' => $path,
			'template' => $this->template(),
		]);

		if ($transcode->exists()) {
			return $transcode;
		}

		$format ??= X264::class;
		if (!is_subclass_of($format, DefaultVideo::class)) {
			throw new \Exception('Invalid FFmpeg format class');
		}

		$format = new $format();

		$this->openVideo()->save($format, $path);
		return $transcode;
	}

	/**
	 * Invalidate the videos transcoded files
	 */
	public function invalidate(): void
	{
		$this->dimensions = null;
		foreach (Dir::read($this->parent()->root()) as $file) {
			if (str_starts_with($file, $this->filename())) {
				F::remove($this->parent()->root() . '/' . $file);
			}
		}
	}

	/**
	 * Create a Video class from a File object
	 */
	public static function from(File $file): static|null
	{
		if ($file->type() !== 'video') {
			return null;
		}

		return new static([
			'filename' => $file->filename(),
			'parent' => $file->parent(),
			'root' => $file->root(),
			'template' => $file->template(),
			'url' => $file->url()
		]);
	}

	/**
	 * Returns the model's UUID
	 * Disable UUIDs for transcoded files to avoid creating a new content file
	 */
	public function uuid(): ?Uuid
	{
		if ($this->exists()) {
			return parent::uuid();
		}

		return null;
	}
}
