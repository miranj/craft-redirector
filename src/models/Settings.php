<?php
/**
 * Redirector settings
 *
 * @link      https://miranj.in/
 * @copyright Copyright (c) 2021 Miranj Design LLP
 */

namespace miranj\redirector\models;

use craft\base\Model;

class Settings extends Model
{
    // Public Properties
    // =========================================================================

    /**
     * Handle of the field that captures URLs to be redirected.
     * @var string
     */
    public $redirectField;

    /**
     * Strictly match trailing slash in 404 URLs while matching redirect URLs.
     * @var bool
     */
    public $matchTrailingSlashes = false;

    /**
     * Strictly match query string in 404 URLs while matching redirect URLs.
     * @var bool
     */
    public $matchQueryString = false;

    /**
     * Should the query string from the 404 URL be retained in the redirected URL.
     * @var bool
     */
    public $preserveQueryString = false;

    /**
     * Element types that will contain the $redirectField and should be searched
     * when looking for a matching redirect.
     * @var array
     */
    public $elementTypes = [
        \craft\elements\Entry::class,
        \craft\elements\Category::class,
        \craft\elements\Tag::class,
    ];

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [['redirectField'], 'string'],
            [
                [
                    'matchTrailingSlashes',
                    'matchQueryString',
                    'preserveQueryString',
                ],
                'boolean',
            ],
        ];
    }
}
