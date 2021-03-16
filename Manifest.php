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
 * Written by Matt Saladna <matt@apisnetworks.com>, June 2020
 */

namespace Module\Support\Webapps\App\Type\Adhoc;

use Auth\Sectoken;
use Illuminate\Contracts\Support\Arrayable;
use Module\Support\Webapps\App\Type\Unknown\Handler as Unknown;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

class Manifest implements \ArrayAccess, Arrayable {
	use \apnscpFunctionInterceptorTrait;
	use \ContextableTrait;
	use \FilesystemPathTrait;

	public const MANIFEST_FILE = '.webapp.yml';
	protected const SIGNING_KEY = 'signature';
	protected const MANIFEST_RELEASE = '1.0';

	protected $meta = [];

	/**
	 * @var Unknown
	 */
	protected $app;
	/**
	 * @var bool Manifest is valid
	 */
	private $signed;

	protected function __construct(Unknown $app)
	{
		$this->app = $app;
		if (!$app->contextSynchronized($this->getAuthContext())) {
			fatal('Context mismatch');
		}

		try {
			$this->meta = (array)$this->getManifest();
		} catch (ParseException $e) {
			$this->signed = false;
			error($e->getMessage());
		}
	}

	/**
	 * Seal a manifest
	 *
	 * @return bool
	 */
	public function sign(): bool
	{
		$salt = (string)Sectoken::instantiateContexted($this->getAuthContext());
		if (!$salt) {
			return error('Cannot sign manifest without user logging into panel directly first!');
		}

		$this->meta['manifest_version'] = self::MANIFEST_RELEASE;

		$hashed = $this->hash($this->meta, $salt);
		$this->meta['signature'] = $hashed;
		try {
			$this->getManifest();
		} catch (ParseException $e) {
			return error("Failed to sign manifest: %s", $e->getMessage());
		}
		if (!$this->verifySignature($hashed)) {
			return error('Manifest failed self-assessment');
		}

		$this->signed = true;
		return $this->file_put_file_contents(
			$this->getManifestPath(),
			Yaml::dump($this->meta, 4, 2)
		);
	}

	/**
	 * Hash data
	 *
	 * @param array  $metadata
	 * @param string $salt
	 * @return string
	 */
	private function hash(array $metadata, string $salt): string
	{
		unset($metadata[self::SIGNING_KEY]);

		return hash_pbkdf2('sha512', serialize($metadata), $salt, 15000);
	}

	/**
	 * Manifest is valid
	 *
	 * @param string|null $signature
	 * @return bool
	 */
	public function verifySignature(string $signature = null): bool
	{
		$salt = (string)Sectoken::instantiateContexted($this->getAuthContext());

		if (!$salt) {
			return error('Cannot verify manifest without security token');
		}

		if (null === $signature) {
			$signature = array_get($this->meta, self::SIGNING_KEY);
		}
		return $this->hash($this->meta, $salt) === $signature;
	}

	/**
	 * Manifest has signature
	 *
	 * @return bool
	 */
	public function hasSignature(): bool
	{
		return isset($this->meta[self::SIGNING_KEY]);
	}

	/**
	 * Get manifest data
	 *
	 * @return array|null manifest metadata
	 */
	protected function getManifest(): ?array
	{
		$file = $this->getManifestPath();
		if (!$this->file_exists($file)) {
			return null;
		}

		$contents = $this->file_get_file_contents($file);

		return Yaml::parse($contents);
	}

	/**
	 * Manifest file exists
	 *
	 * @return bool
	 */
	public function exists(): bool
	{
		return $this->file_exists($this->getManifestPath());
	}

	/**
	 * @return bool
	 */
	public function create(): bool
	{
		if ($this->exists()) {
			return error('Manifest already exists');
		}

		$path = $this->getManifestPath();
		$template = file_get_contents(resource_path('storehouse/webapp-adhoc.yml'));

		return $this->file_put_file_contents($path, $template) &&
			($this->meta = $this->getManifest()) && $this->sign();
	}

	/**
	 * Get path to manifest file
	 *
	 * @return string
	 */
	public function getManifestPath(): string
	{
		return $this->app->getAppRoot() . '/' . self::MANIFEST_FILE;
	}

	public function offsetExists($offset)
	{
		return isset($this->meta[$offset]);
	}

	public function offsetGet($offset)
	{
		if ($this->signed === null) {
			$this->signed = $this->verifySignature();
		}
		return $this->signed ? ($this->meta[$offset] ?? null) : null;
	}

	public function offsetSet($offset, $value)
	{
		$this->meta[$offset] = $value;
	}

	public function offsetUnset($offset)
	{
		unset($this->meta[$offset]);
	}

	public function toArray()
	{
		return $this->meta;
	}
}
