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
     * Handle of field that captures legacy paths
     * @var string
     */
    public $fieldHandle;
    
    /**
     * Strictly match trailing slash in 404 URLs while matching legacy URLs
     * @var bool
     */
    public $matchTrailingSlashes = false;
    
    /**
     * Strictly match query string in 404 URLs while matching legacy URLs
     * @var bool
     */
    public $matchQueryString = false;
    
    /**
     * Append query string to the final redirected URLs
     * @var bool
     */
    public $preserveQueryString = true;
    
    
    
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['fieldHandle'], 'string'],
            [['matchTrailingSlashes', 'matchQueryString', 'preserveQueryString'], 'boolean'],
        ];
    }
}
