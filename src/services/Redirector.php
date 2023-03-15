<?php

namespace miranj\redirector\services;

use Craft;
use craft\base\Component;
use craft\elements\Entry;
use craft\elements\Category;
use craft\elements\Tag;
use craft\helpers\UrlHelper;
use miranj\redirector\Plugin;

/**
 * Redirector Service
 *
 * @since 1.0.0
 */
class Redirector extends Component
{
    protected $settings = null;

    protected function getSettings()
    {
        if ($this->settings === null) {
            $this->settings = Plugin::getInstance()->settings;
        }
        return $this->settings;
    }

    // Public Methods
    // =========================================================================

    /**
     * Handle 404s by looking for a matching redirect for the currently
     * requested URL, and redirecting to that element's URI, if found
     */
    public function handle404()
    {
        Craft::info(
            Craft::t('redirector', 'A 404 exception occurred'),
            __METHOD__,
        );

        // Build the request path + query string
        // eg: from https://miranj.in/work?token=hello
        //     to   /work?token=hello
        $request = Craft::$app->getRequest();
        $path = implode(
            '?',
            array_filter([
                '/' . $request->getPathInfo(true),
                urldecode($request->getQueryStringWithoutPath()),
            ]),
        );

        // Log the 404 path
        Craft::info(
            Craft::t('redirector', '404 path: {path}', ['path' => $path]),
            __METHOD__,
        );

        // Search for a matching redirect
        $targetElement = $this->findElementWithRedirectFieldMatch($path);
        if ($targetElement) {
            $this->redirectToUrl($targetElement->getUrl());
        }
    }

    /**
     * Returns all variants of the $path that will be queried
     * looking for a matching redirect in the elements with the
     * redirect field.
     *
     * @param string $path The path to be queried for redirect matches
     * @return array List of search strings to be queried
     */
    public function getPathSearchStrings(string $path): array
    {
        $pathSansQueryString = UrlHelper::stripQueryString($path);
        $queryString = str_replace($pathSansQueryString, '', $path);

        // if trailing slash matches are disabled, normalise path
        // and look for both trailing and non-trailing versions
        $searchStrings = $this->getSettings()->matchTrailingSlashes
            ? [$pathSansQueryString]
            : [
                rtrim($pathSansQueryString, '/'),
                rtrim($pathSansQueryString, '/') . '/',
            ];

        // if query string matches are disabled,
        // search for both query & non-query string paths,
        // otherwise only query string paths
        if ($queryString !== '' && is_string($queryString)) {
            $searchStrings = array_merge(
                // non-query string paths
                !$this->getSettings()->matchQueryString ? $searchStrings : [],

                // query string paths
                array_map(fn($p) => $p . $queryString, $searchStrings),
            );
        }

        // add versions of the search string with '+' in place of spaces
        // because Craft forefully normalises all spaces to '+'
        // https://github.com/craftcms/cms/blob/main/src/fields/Url.php#L182
        if (strpos($path, ' ') !== false) {
            $searchStrings = array_unique(
                array_merge(
                    $searchStrings,
                    array_map(
                        fn($p) => str_replace(' ', '+', $p),
                        $searchStrings,
                    ),
                ),
            );
        }

        return $searchStrings;
    }

    /**
     * @param string $path
     * @return object|null
     */
    public function findElementWithRedirectFieldMatch(string $path)
    {
        /**
         * Base search criteria:
         * 1. Element should have a path
         * 2. Legacy URL field should match the path (with or without query string)
         */
        $searchCriteria = [
            'uri' => ':notempty:',
            $this->getSettings()->redirectField => $this->getPathSearchStrings(
                $path,
            ),
        ];

        // Search through all element types until a match is found
        $targetElement = null;
        $elementTypes = $this->getSettings()->elementTypes;
        while (!$targetElement && ($elementType = array_shift($elementTypes))) {
            $query = $elementType::find();
            Craft::configure($query, $searchCriteria);
            $targetElement = $query->one();
        }

        return $targetElement;
    }

    /**
     * @param string $redirectUrl
     * @param int $status
     */
    public function redirectToUrl(string $redirectUrl, int $status = 302)
    {
        if (!$redirectUrl) {
            return;
        }

        $response = Craft::$app->getResponse();
        $request = Craft::$app->getRequest();

        // If this isn't a full URL, make it one based on the appropriate site
        if (!UrlHelper::isFullUrl($redirectUrl)) {
            try {
                $redirectUrl = UrlHelper::siteUrl(
                    $redirectUrl,
                    null,
                    null,
                    Craft::$app->getSites()->currentSite->id ?? null,
                );
            } catch (\yii\base\Exception $e) {
            }
        }

        // Append request query string if `preserveQueryString` is set
        if ($this->getSettings()->preserveQueryString) {
            $pathQueryString = $request->getQueryStringWithoutPath();
            if (!empty($pathQueryString)) {
                $redirectUrl .= '?' . $pathQueryString;
            }
        }

        // Log redirection
        Craft::info(
            Craft::t(
                'redirector',
                'Redirecting {url} to {dest} with status {status}',
                [
                    'url' => $request->getAbsoluteUrl(),
                    'dest' => $redirectUrl,
                    'status' => $status,
                ],
            ),
            __METHOD__,
        );

        // Redirect the request
        $response->redirect($redirectUrl, $status)->send();

        try {
            Craft::$app->end();
        } catch (ExitException $e) {
            Craft::error($e->getMessage(), __METHOD__);
        }
    }
}
