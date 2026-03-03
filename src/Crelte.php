<?php

namespace crelte\crelte;

use Craft;
use craft\base\Element;
use craft\helpers\App;
use yii\base\Event;
use craft\base\Model;
use craft\base\Plugin;
use craft\events\RegisterCacheOptionsEvent;
use craft\events\RegisterGqlQueriesEvent;
use craft\events\RegisterGqlTypesEvent;
use craft\events\RegisterGqlSchemaComponentsEvent;
use craft\events\RegisterCpAlertsEvent;
use craft\services\Gql;
use craft\gql\GqlEntityRegistry;
use craft\helpers\{Cp, ElementHelper, Html, UrlHelper};
use craft\utilities\ClearCaches;
use craft\web\Application;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use crelte\crelte\models\Settings;
use crelte\crelte\gql\queries\{SitesQuery, StarterQuery};

// These are a list of classnames which should be ignored,
// because they are not important for the queries cache
const IGNORE_ELEMENT_SAVE = [
	'craft\elements\User',
	'verbb\formie\elements\Submission',
	'craft\commerce\elements\Order',
	'craft\elements\Address'
];

/**
 * craft-crelte plugin
 *
 * @method static GraphqlSites getInstance()
 * @method Settings getSettings()
 */
class Crelte extends Plugin
{
	public string $schemaVersion = "1.0.0";

	private bool $webhookRequested = false;

	public const EDITION_SOLO = "solo";
	public const EDITION_PRO = "pro";

	public static function editions(): array
	{
		return [self::EDITION_SOLO, self::EDITION_PRO];
	}

	public function init(): void
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
			$event->queries = [
				...$event->queries,
				...SitesQuery::getQueries(),
				...($inDevMode ? StarterQuery::getQueries() : [])
			];
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
					\sprintf(
						'<a class="go" href="%s">%s</a>',
						UrlHelper::cpUrl("plugin-store/craft-crelte"),
						Craft::t("app", "Resolve now")
					) .
					Html::endTag("p"),
				"showIcon" => false,
			];
		});

		$enableCaching = App::env("CACHING") === true;
		// for local dev CRAFT_FRONTEND_URL should be http://host.docker.internal:8080
		$frontendUrl = App::env("CRAFT_FRONTEND_URL") ?: App::env("FRONTEND_URL") ?: null;
		$token = App::env("ENDPOINT_TOKEN") ?: null;
		if ($enableCaching && $frontendUrl && $token) {
			$this->enableQueriesWebhook($frontendUrl, $token);
		}
	}

	private function enableQueriesWebhook(
		string $frontendUrl,
		string $token
	): void {
		// Add a new "clear cache" checkbox option
		Event::on(
			ClearCaches::class,
			ClearCaches::EVENT_REGISTER_CACHE_OPTIONS,
			function (RegisterCacheOptionsEvent $event) {
				$event->options[] = [
					'key' => 'crelte-queries-cache',
					'label' => 'Crelte queries cache',
					'info' => 'Clears crelte queries cache',
					'action' => function () {
						$this->webhookRequested = true;
					},
				];
			}
		);


		// Listen for element save events
		Event::on(Element::class, Element::EVENT_AFTER_SAVE, function (
			Event $event
		) {
			/** @var Element $element */
			$element = $event->sender;

			if (ElementHelper::isDraftOrRevision($element)) {
				return;
			}

			$className = \get_class($element);
			if (\in_array($className, IGNORE_ELEMENT_SAVE, true)) {
				return;
			}
			// Craft::info("event after save " . $className);

			// for this to work correctly you need to setup like
			// https://github.com/craftcms/cms/pull/17024
			// */5 * * * *
			$this->webhookRequested = true;
		});

		// Listen for element delete events
		Event::on(Element::class, Element::EVENT_AFTER_DELETE, function (
			Event $event
		) {
			$this->webhookRequested = true;
		});

		// Check after each request if webhook should be triggered
		Event::on(
			Application::class,
			Application::EVENT_AFTER_REQUEST,
			function () use ($frontendUrl, $token) {
				if ($this->webhookRequested) {
					$this->webhookRequested = false;
					$this->callWebhook($frontendUrl, $token);
				}
			}
		);
	}

	private function callWebhook(string $frontendUrl, string $token): void
	{
		try {
			$client = new Client();
			$webhookUrl = rtrim($frontendUrl, "/") . "/queries/webhook";

			$response = $client->post($webhookUrl, [
				"timeout" => 10,
				"headers" => [
					"Content-Type" => "application/json",
					"Authorization" => "Bearer " . $token,
				],
				"json" => [
					"timestamp" => time(),
					"source" => "craft-crelte",
				],
			]);

			if ($response->getStatusCode() >= 400) {
				Craft::error(
					"Webhook call failed with status: " .
						$response->getStatusCode(),
					__METHOD__
				);
			} else {
				Craft::info("crelte queries cache webhook called");
			}
		} catch (RequestException $e) {
			Craft::error(
				"Failed to call webhook: " . $e->getMessage(),
				__METHOD__
			);
		} catch (\Exception $e) {
			Craft::error(
				"Unexpected error calling webhook: " . $e->getMessage(),
				__METHOD__
			);
		}
	}

	protected function createSettingsModel(): ?Model
	{
		return Craft::createObject(Settings::class);
	}
}
