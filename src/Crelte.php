<?php

namespace crelte\crelte;

use Craft;
use yii\base\Event;
use craft\base\Model;
use craft\base\Plugin;
use craft\events\RegisterGqlQueriesEvent;
use craft\events\RegisterGqlTypesEvent;
use craft\events\RegisterGqlSchemaComponentsEvent;
use craft\events\RegisterCpAlertsEvent;
use craft\services\Gql;
use craft\gql\GqlEntityRegistry;
use craft\helpers\{Cp, Html, UrlHelper};
use crelte\crelte\models\Settings;
use crelte\crelte\gql\queries\{SitesQuery, StarterQuery};

/**
 * craft-crelte plugin
 *
 * @method static GraphqlSites getInstance()
 * @method Settings getSettings()
 */
class Crelte extends Plugin
{
	public string $schemaVersion = "1.0.0";

	const EDITION_SOLO = "solo";
	const EDITION_PRO = "pro";

	public static function editions(): array
	{
		return [self::EDITION_SOLO, self::EDITION_PRO];
	}

	public function init()
	{
		parent::init();

		$inDevMode = Craft::$app->getConfig()->getGeneral()->devMode;

		// Handler: Gql::EVENT_REGISTER_GQL_TYPES
		Event::on(Gql::class, Gql::EVENT_REGISTER_GQL_TYPES, static function (
			RegisterGqlTypesEvent $event
		) use ($inDevMode) {
			GqlEntityRegistry::createEntity(
				"CrelteSiteGroup",
				SitesQuery::getSiteGroupType()
			);
			GqlEntityRegistry::createEntity(
				"CrelteSite",
				SitesQuery::getSiteType()
			);

			if ($inDevMode) {
				GqlEntityRegistry::createEntity(
					"CrelteStarterEntry",
					StarterQuery::getEntryType()
				);
				GqlEntityRegistry::createEntity(
					"CrelteStarterGlobal",
					StarterQuery::getGlobalType()
				);
			}
		});

		Event::on(Gql::class, Gql::EVENT_REGISTER_GQL_QUERIES, function (
			RegisterGqlQueriesEvent $event
		) use ($inDevMode) {
			$event->queries = array_merge(
				$event->queries,
				SitesQuery::getQueries(),
				$inDevMode ? StarterQuery::getQueries() : []
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

		Event::on(Cp::class, Cp::EVENT_REGISTER_ALERTS, function (
			RegisterCpAlertsEvent $event
		) {
			if (
				self::getInstance()->is(self::EDITION_PRO) ||
				Craft::$app->getEdition() === Craft::Solo
			) {
				return;
			}

			$event->alerts[] = [
				"content" =>
					Html::tag("h2", "Edition change required") .
					Html::tag(
						"p",
						"The Solo edition is only available in combination with Craft Solo"
					) .
					// can't use Html::a() because it's encoding &amp;'s, which is causing issues
					Html::beginTag("p", [
						"class" => ["flex", "flex-nowrap"],
					]) .
					sprintf(
						'<a class="go" href="%s">%s</a>',
						UrlHelper::cpUrl("plugin-store/craft-crelte"),
						Craft::t("app", "Resolve now")
					) .
					Html::endTag("p"),
				"showIcon" => false,
			];
		});
	}

	protected function createSettingsModel(): ?Model
	{
		return Craft::createObject(Settings::class);
	}
}
