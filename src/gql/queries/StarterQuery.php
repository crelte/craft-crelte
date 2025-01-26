<?php

namespace crelte\crelte\gql\queries;

use Craft;
use crelte\crelte\gql\resolvers\{StarterEntryResolver, StarterGlobalsResolver};
use crelte\crelte\helpers\Gql as GqlHelper;
use GraphQL\Type\Definition\{Type, ObjectType};
use craft\gql\GqlEntityRegistry;
use craft\gql\types\QueryArgument;

use craft\gql\base\Query;

class StarterQuery extends Query
{
	public static function getQueries(bool $checkToken = true): array
	{
		// you can only query this in devMode

		return [
			"crelteStarterEntry" => [
				"type" => GqlEntityRegistry::getEntity("CrelteStarterEntry"),
				"args" => [
					"uri" => Type::listOf(Type::string()),
					"siteId" => Type::listOf(QueryArgument::getType()),
				],
				"resolve" => StarterEntryResolver::class . "::resolve",
				"description" => "This query is used as a starter query",
			],
			"crelteStarterGlobals" => [
				"type" => Type::listOf(
					GqlEntityRegistry::getEntity("CrelteStarterGlobal")
				),
				"args" => [
					"handle" => Type::string(),
					"siteId" => Type::listOf(QueryArgument::getType()),
				],
				"resolve" => StarterGlobalsResolver::class . "::resolve",
				"description" => "This query is used as a starter query",
			],
		];
	}

	static function getEntryType()
	{
		return new ObjectType([
			"name" => "CrelteStarterEntry",
			"fields" => [
				"id" => Type::int(),
				"siteId" => Type::int(),
				"sectionHandle" => Type::string(),
				"typeHandle" => Type::string(),
				"title" => Type::string(),
				"cpUrl" => Type::string(),
			],
		]);
	}

	static function getGlobalType()
	{
		return new ObjectType([
			"name" => "CrelteStarterGlobal",
			"fields" => [
				"siteId" => Type::int(),
				"title" => Type::string(),
			],
		]);
	}
}
