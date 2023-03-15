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
use craft\events\DefineRulesEvent;
use craft\events\ExceptionEvent;
use craft\helpers\UrlHelper;
use craft\services\Plugins;
use craft\web\ErrorHandler;
use miranj\redirector\models\Settings;
use miranj\redirector\services\Redirector;
use yii\base\Event;
use yii\web\HttpException;

class Plugin extends BasePlugin
{
    /**
     * @var Settings
     */
    public static $fieldExists = false;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        // Set services as components
        $this->set('redirector', Redirector::class);

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
        if (!self::$fieldExists) {
            return;
        }

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

        // If this is a 404 error, see if we can handle it
        if (
            $exception instanceof HttpException &&
            $exception->statusCode === 404
        ) {
            $this->get('redirector')->handle404();
        }
    }

    public function filterRedirectField(DefineRulesEvent $event)
    {
        if (!self::$fieldExists) {
            return;
        }

        /** @var Element */
        $element = $event->sender;

        // Ignore element types that don't have their own pages,
        // or are not in the configured include list
        if (
            !$element->hasUris() ||
            !in_array(get_class($element), $this->settings->elementTypes)
        ) {
            return;
        }

        // Ignore elements that don't use this field
        if (
            !$element
                ->getFieldLayout()
                ->isFieldIncluded($this->settings->redirectField)
        ) {
            return;
        }

        // URL decode
        $event->rules[] = [
            'field:' . $this->settings->redirectField,
            'filter',
            'filter' => 'urldecode',
            'skipOnEmpty' => true,
            'skipOnArray' => true,
        ];

        // Transform redirect URLs to be relative (domain-independent)
        $event->rules[] = [
            'field:' . $this->settings->redirectField,
            'filter',
            'filter' => [UrlHelper::class, 'rootRelativeUrl'],
            'skipOnEmpty' => true,
            'skipOnArray' => true,
        ];
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

        Event::on(Element::class, Element::EVENT_DEFINE_RULES, [
            $this,
            'filterRedirectField',
        ]);
    }
}
