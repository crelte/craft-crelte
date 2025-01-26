<?php
namespace crelte\crelte\gql\resolvers;

use Craft;
use craft\gql\base\Resolver;

use GraphQL\Type\Definition\ResolveInfo;

class StarterGlobalsResolver extends Resolver
{
	public static function resolve(
		mixed $source,
		array $arguments,
		mixed $context,
		ResolveInfo $resolveInfo
	): mixed {
		$handle = $arguments["handle"] ?? null;
		$siteId = $arguments["siteId"][0] ?? null;
		if ($siteId !== "*") {
			throw new \Exception("siteId must be '*' for now");
		}

		if ($handle !== "header") {
			return [];
		}

		// get all sites
		$sites = Craft::$app->sites->allSites;
		$globals = [];

		foreach ($sites as $site) {
			$globals[] = [
				"siteId" => $site->id,
				"title" => $site->name,
			];
		}

		return $globals;
	}
}
