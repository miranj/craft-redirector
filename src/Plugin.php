<?php
/**
 * Redirector plugin
 *
 * @link      https://miranj.in/
 * @copyright Copyright (c) 2021 Miranj Design LLP
 */

namespace miranj\redirector;

use Craft;
use craft\base\Element;
use craft\base\Plugin as BasePlugin;
use craft\events\ModelEvent;
use craft\events\ExceptionEvent;
use craft\helpers\ElementHelper;
use craft\helpers\UrlHelper;
use craft\services\Plugins;
use craft\web\ErrorHandler;
use miranj\redirector\models\Settings;
use miranj\redirector\services;
use yii\base\Event;
use yii\validators\FilterValidator;
use yii\web\HttpException;

class Plugin extends BasePlugin
{
    /**
     * @var Settings
     */
    public static $fieldExists = false;

    public static function config(): array
    {
        // Set services as components
        return [
            'components' => [
                'redirector' => services\Redirector::class,
            ],
        ];
    }

    public function init(): void
    {
        parent::init();

        $this->addEventListeners();

        Craft::info(
            Craft::t('redirector', '{name} plugin loaded', [
                'name' => $this->name,
            ]),
            __METHOD__,
        );
    }

    public function onAfterLoadPlugins()
    {
        // Verify that the URL redirect field exists
        self::$fieldExists =
            !!$this->settings->redirectField &&
            !!Craft::$app->fields->getFieldByHandle(
                $this->settings->redirectField,
            );
    }

    public function onBeforeHandleException(ExceptionEvent $event)
    {
        $exception = $event->exception;
        $request = Craft::$app->getRequest();

        // Handle only front-end web requests
        if (!$request->getIsSiteRequest() || $request->getIsConsoleRequest()) {
            return;
        }

        Craft::debug('ErrorHandler::EVENT_BEFORE_HANDLE_EXCEPTION', __METHOD__);

        // If this is a Twig Runtime exception, use the previous one instead
        if (
            $exception instanceof \Twig\Error\RuntimeError &&
            ($previousException = $exception->getPrevious()) !== null
        ) {
            $exception = $previousException;
        }

        // Only proceed if this is a 404 error
        if (
            !($exception instanceof HttpException) ||
            $exception->statusCode !== 404
        ) {
            return;
        }

        // Field based redirects
        if (self::$fieldExists) {
            $this->redirector->handle404ForFieldBasedRedirect();
        }
    }

    // Apply filters to sanitise and prep data in the redirect field
    // during EVENT_BEFORE_SAVE (instead of element validation rules)
    // see: https://github.com/craftcms/cms/issues/15509
    public function filterRedirectField(ModelEvent $event)
    {
        if (!self::$fieldExists) {
            return;
        }

        /** @var Element */
        $element = $event->sender;

        if ($element->propagating) {
            Craft::debug("Ignore propagating element: $element", __METHOD__);
            return;
        }

        // ignore drafts, revisions, provisional drafts, etc
        if (
            !ElementHelper::isCanonical($element) ||
            (!$element->firstSave && ElementHelper::isDraftOrRevision($element))
        ) {
            Craft::debug("Ignore non-canonical element: $element", __METHOD__);
            return;
        }

        // Ignore element types that don't have their own pages,
        // or are not in the configured include list
        if (
            !$element->hasUris() ||
            !in_array(get_class($element), $this->settings->elementTypes)
        ) {
            Craft::debug(
                "Ignore non-uri or unsupported element: $element",
                __METHOD__,
            );
            return;
        }

        // Ignore elements that don't use this field
        if (
            !(
                $element
                    ->getFieldLayout()
                    ->isFieldIncluded($this->settings->redirectField) ?? false
            ) ||
            !$element->getFieldValue($this->settings->redirectField)
        ) {
            Craft::debug(
                "Field {$this->settings->redirectField} empty or not found in element: $element",
                __METHOD__,
            );
            return;
        }

        // Apply filters
        $filterRules = [];
        Craft::debug(
            "Applying URL cleaning rules for element: $element",
            __METHOD__,
        );

        // URL decode
        $filterRules[] = [
            'filter' => 'urldecode',
            'skipOnEmpty' => true,
            'skipOnArray' => true,
        ];

        // Transform redirect URLs to be relative (domain-independent)
        $filterRules[] = [
            'filter' => [UrlHelper::class, 'rootRelativeUrl'],
            'skipOnEmpty' => true,
            'skipOnArray' => true,
        ];

        foreach ($filterRules as $filterConfig) {
            $validator = new FilterValidator($filterConfig);
            $validator->validateAttribute(
                $element,
                'field:' . $this->settings->redirectField,
            );
        }

        Craft::debug(
            "Cleaned field {$this->settings->redirectField}: {$element->{$this->settings->redirectField}}",
            __METHOD__,
        );
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function createSettingsModel(): Settings
    {
        return new Settings();
    }

    protected function addEventListeners()
    {
        Event::on(Plugins::class, Plugins::EVENT_AFTER_LOAD_PLUGINS, [
            $this,
            'onAfterLoadPlugins',
        ]);

        Event::on(
            ErrorHandler::class,
            ErrorHandler::EVENT_BEFORE_HANDLE_EXCEPTION,
            [$this, 'onBeforeHandleException'],
        );

        Event::on(Element::class, Element::EVENT_BEFORE_SAVE, [
            $this,
            'filterRedirectField',
        ]);
    }
}
