<?php
namespace crelte\crelte\gql\resolvers;

use Craft;
use craft\gql\base\Resolver;
use craft\helpers\UrlHelper;

use GraphQL\Type\Definition\ResolveInfo;

class StarterEntryResolver extends Resolver
{
	public static function resolve(
		mixed $source,
		array $arguments,
		mixed $context,
		ResolveInfo $resolveInfo
	): mixed {
		$uri = $arguments["uri"][0] ?? null;
		$siteId = $arguments["siteId"][0] ?? null;
		if ($uri !== "__home__") {
			return null;
		}

		return [
			"id" => 1,
			"siteId" => (int) $siteId,
			"sectionHandle" => "crelte",
			"typeHandle" => "starter",
			"title" => "title",
			"cpUrl" => UrlHelper::cpUrl(),
		];
	}
}
