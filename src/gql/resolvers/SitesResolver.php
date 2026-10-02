<?php

namespace crelte\crelte\gql\resolvers;

use Craft;
use craft\gql\base\Resolver;
use yii\web\BadRequestHttpException;

use GraphQL\Type\Definition\ResolveInfo;

class SitesResolver extends Resolver
{
	public static function resolve(
		mixed $source,
		array $arguments,
		mixed $context,
		ResolveInfo $resolveInfo
	): mixed {
		$sitesService = Craft::$app->getSites();
		$availableSites = $sitesService->getAllSites(false);
		$request = Craft::$app->getRequest();
		$siteToken = $request->getIsConsoleRequest() ? null : $request->getSiteToken();

		if ($siteToken) {
			$siteId = Craft::$app->getSecurity()->validateData($siteToken);
			$previewSite = is_numeric($siteId)
				? $sitesService->getSiteById((int)$siteId, true)
				: null;

			if (!$previewSite) {
				throw new BadRequestHttpException("Invalid site token");
			}
			if (!$previewSite->enabled) {
				$availableSites[] = $previewSite;
			}
		}

		$sites = [];

		foreach ($availableSites as $site) {
			if (!$site->hasUrls) {
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
