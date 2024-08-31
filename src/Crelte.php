<?php

namespace crelte\crelte;

use Craft;
use yii\base\Event;
use craft\base\Model;
use craft\base\Plugin;
use craft\events\RegisterGqlQueriesEvent;
use craft\events\RegisterGqlTypesEvent;
use craft\events\RegisterGqlSchemaComponentsEvent;
use craft\services\Gql;
use craft\gql\GqlEntityRegistry;
use crelte\crelte\models\Settings;
use crelte\crelte\gql\queries\SitesQuery;

/**
 * craft-crelte plugin
 *
 * @method static GraphqlSites getInstance()
 * @method Settings getSettings()
 */
class Crelte extends Plugin
{
	public string $schemaVersion = "1.0.0";

	public function init()
	{
		parent::init();

		// Handler: Gql::EVENT_REGISTER_GQL_TYPES
		Event::on(Gql::class, Gql::EVENT_REGISTER_GQL_TYPES, static function (
			RegisterGqlTypesEvent $event
		) {
			GqlEntityRegistry::createEntity(
				"CrelteSiteGroup",
				SitesQuery::getSiteGroupType()
			);
			GqlEntityRegistry::createEntity(
				"CrelteSite",
				SitesQuery::getSiteType()
			);
		});

		Event::on(Gql::class, Gql::EVENT_REGISTER_GQL_QUERIES, function (
			RegisterGqlQueriesEvent $event
		) {
			$event->queries = array_merge(
				$event->queries,
				SitesQuery::getQueries()
			);
		});

		Event::on(
			Gql::class,
			Gql::EVENT_REGISTER_GQL_SCHEMA_COMPONENTS,
			static function (RegisterGqlSchemaComponentsEvent $event) {
				$event->queries["Crelte"]["crelte.all:read"] = [
					"label" => "Allow to list sites",
				];
			}
		);
	}

	protected function createSettingsModel(): ?Model
	{
		return Craft::createObject(Settings::class);
	}
}