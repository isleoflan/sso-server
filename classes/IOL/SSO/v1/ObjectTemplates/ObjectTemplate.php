<?php

declare(strict_types=1);

namespace IOL\SSO\v1\ObjectTemplates;

use IOL\SSO\v1\Exceptions\InvalidObjectValueException;

class ObjectTemplate
{
    protected array $template;

    /**
     * @throws InvalidObjectValueException
     */
    public function __construct(array $input)
    {
        foreach(array_keys($input) as $validationKey){
            if(!isset($template[$validationKey])){
                throw new InvalidObjectValueException('Object did not expect a value for '.$validationKey);
            }
        }
    }
}