<?php

namespace crelte\crelte\gql\resolvers;

use Craft;
use craft\gql\base\Resolver;

use GraphQL\Type\Definition\ResolveInfo;

class SitesResolver extends Resolver
{
	public static function resolve(
		mixed $source,
		array $arguments,
		mixed $context,
		ResolveInfo $resolveInfo
	): mixed {
		$sites = [];

		foreach (Craft::$app->sites->allSites as $site) {
			if (!$site->enabled || !$site->hasUrls) {
				continue;
			}

			$sites[] = [
				"id" => $site->id,
				"baseUrl" => $site->baseUrl,
				"language" => $site->language,
				"name" => $site->name,
				"handle" => $site->handle,
				"primary" => $site->primary,
				"group" => [
					"id" => $site->group->id,
					"name" => $site->group->name,
				],
			];
		}

		return $sites;
	}
}
