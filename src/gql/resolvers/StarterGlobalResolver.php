<?php
namespace crelte\crelte\gql\resolvers;

use Craft;
use craft\gql\base\Resolver;

use GraphQL\Type\Definition\ResolveInfo;

class StarterGlobalResolver extends Resolver
{
	public static function resolve(
		mixed $source,
		array $arguments,
		mixed $context,
		ResolveInfo $resolveInfo
	): mixed {
		$handle = $arguments["handle"] ?? null;
		$siteId = $arguments["siteId"][0] ?? null;
		if ($siteId === "*" || !$siteId) {
			throw new \Exception("siteId must be a number");
		}

		if ($handle !== "header") {
			return [];
		}

		$site = Craft::$app->sites->getSiteById($siteId);
		if (!$site) {
			throw new \Exception("Site not found");
		}

		return [
			"siteId" => $site->id,
			"title" => $site->name,
		];
	}
}
