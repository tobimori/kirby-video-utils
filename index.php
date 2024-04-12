<?php

@include_once __DIR__ . '/vendor/autoload.php';

use Kirby\Cms\App;
use Kirby\Cms\File;
use Kirby\Cms\Files;
use Kirby\Content\Field;
use tobimori\VideoUtils\Video;

App::plugin('tobimori/video-utils', [
	'fieldMethods' => [
		/**
		 * Returns a file object from a filename in the field
		 */
		'toVideo' => function (Field $field): Video|null {
			return $field->toVideos()->first();
		},

		/**
		 * Returns a file collection from a yaml list of filenames in the field
		 */
		'toVideos' => function (
			Field $field,
			string $separator = 'yaml'
		): Files {
			$parent = $field->parent();
			$files  = new Files([]);

			foreach ($field->toData($separator) as $id) {
				if (is_string($id) === true && $file = $parent->kirby()->file($id, $parent)) {
					$files->add($file->toVideo());
				}
			};

			return $files;
		},
	],
	'fileMethods' => [
		/**
		 * Returns a video object from a file
		 */
		'toVideo' => function (): Video {
			return Video::from($this);
		},
	],
	'hooks' => [
		'file.replace:after' => fn (File $newFile) => $newFile->toVideo()?->invalidate(),
		'file.delete:after' => fn (bool $status, File $file) => $file->toVideo()?->invalidate(),
		'file.changeName:after' => fn (File $newFile, File $oldFile) => $oldFile->toVideo()?->invalidate(),
	]
]);
