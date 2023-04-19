<?php
/**
 * Redirects Controller
 *
 * @link      https://miranj.in/
 * @copyright Copyright (c) 2022 Miranj Design LLP
 */

namespace miranj\redirector\controllers;

use Craft;
use craft\web\Controller;
use craft\web\View;
use yii\web\BadRequestHttpException;
use yii\web\Response;

/**
 * RedirectController
 */
class RedirectController extends Controller
{
    public function init(): void
    {
        $this->allowAnonymous = true;
        parent::init();
    }

    /**
     * @param string $url The redirect URL
     * @param int $statusCode The response status code
     * @return Response
     */
    public function actionIndex(string $url, int $statusCode = 302): Response
    {
        $redirectUrl = $url;

        if ($url) {
            // treat $url as a Twig object template string and pass it
            // the route params to render the target URL for the redirect
            $redirectUrl = Craft::$app
                ->getView()
                ->renderObjectTemplate(
                    $url,
                    Craft::$app->urlManager->getRouteParams(),
                    [],
                    View::TEMPLATE_MODE_SITE,
                );

            // replace only the matched path with the new target path inside
            // the *full* original URL so that pagination params are preserved
            $redirectUrl = str_replace(
                Craft::$app->request->getPathInfo(),
                $redirectUrl,
                Craft::$app->request->fullPath,
            );
        }

        if (!is_string($redirectUrl)) {
            throw new BadRequestHttpException('Invalid URL.');
        }

        return $this->redirect($redirectUrl, $statusCode);
    }
}
