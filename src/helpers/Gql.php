<?php
namespace crelte\crelte\helpers;

use Craft;
use craft\helpers\Gql as GqlHelper;

class Gql extends GqlHelper
{
	public static function canQuerySites(): bool
	{
		$allowedEntities = self::extractAllowedEntitiesFromSchema();

		return isset($allowedEntities["crelte"]);
	}
}
