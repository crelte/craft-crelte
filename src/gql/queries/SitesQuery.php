<?php

namespace crelte\crelte\gql\queries;

use Craft;
use crelte\crelte\gql\resolvers\SitesResolver;
use crelte\crelte\helpers\Gql as GqlHelper;
use GraphQL\Type\Definition\{Type, ObjectType};
use craft\gql\GqlEntityRegistry;

use craft\gql\base\Query;

class SitesQuery extends Query
{
	public static function getQueries(bool $checkToken = true): array
	{
		if ($checkToken && !GqlHelper::canQuerySites()) {
			return [];
		}

		return [
			// c for custom
			"crelteSites" => [
				"type" => Type::listOf(
					GqlEntityRegistry::getEntity("CrelteSite")
				),
				"args" => [],
				"resolve" => SitesResolver::class . "::resolve",
				"description" => "This query is used to query for sites data.",
			],
		];
	}

	static function getSiteGroupType()
	{
		return new ObjectType([
			"name" => "CrelteSiteGroup",
			"fields" => [
				"id" => Type::int(),
				"name" => Type::string(),
			],
		]);
	}

	static function getSiteType()
	{
		return new ObjectType([
			"name" => "CrelteSite",
			"fields" => [
				"id" => Type::int(),
				"baseUrl" => Type::string(),
				"language" => Type::string(),
				"name" => Type::string(),
				"handle" => Type::nonNull(Type::string()),
				"primary" => Type::boolean(),
				"group" => GqlEntityRegistry::getEntity("CrelteSiteGroup"),
			],
		]);
	}
}
