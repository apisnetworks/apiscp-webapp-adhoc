<?php declare(strict_types=1);
/**
 * Copyright (C) Apis Networks, Inc - All Rights Reserved.
 *
 * Unauthorized copying of this file, via any medium, is
 * strictly prohibited without consent. Any dissemination of
 * material herein is prohibited.
 *
 * For licensing inquiries email <licensing@apisnetworks.com>
 *
 * Written by Matt Saladna <matt@apisnetworks.com>, August 2020
 */

namespace Module\Support\Webapps\App\Type\Adhoc;

use Module\Support\Webapps\App\Type\Unknown\Handler as Unknown;

class Handler extends Unknown
{
	use \FilesystemPathTrait;

	const NAME = 'Custom';
	const ADMIN_PATH = '/';
	const LINK = 'https://apiscp.com/';

	public function getVersions(): array
	{
		return [];
	}

	public function display(): bool
	{
		return false;
	}

	public function getClassMapping(): string
	{
		return 'webapp';
	}

	public function getName(): string
	{
		return 'Ad hoc';
	}

	public function detect($mixed, $path = ''): bool
	{
		return file_exists($this->getAppRoot() . '/' . Manifest::MANIFEST_FILE);
	}
}

