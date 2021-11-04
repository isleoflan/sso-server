<?php

declare(strict_types=1);

namespace IOL\SSO\v1\BitMasks;


class BitMask
{
    /**
     * @var int $allowList
     *
     * holds all allowed values from child class.
     * variable is initialized as binary. this should not matter, just done to be safe.
     */
    private int $allowList = 0b0;


    /**
     * @param int|null $value
     *
     * in the constructor, the bit mask can be set directly.
     * e.g.:
     * new RequestMethod(RequestMethod::GET | RequestMethod::POST);
     */
    public function __construct(?int $value = null)
    {
        if(!is_null($value)) {
            $this->allowList = $value;
        }
    }

    /**
     * @return array
     *
     * returns an array with all entries on the allow-list
     */
    public function getValues(): array
    {
        $allowArray = [];

        // Loop through all constants / possible values defined in the child class
        $reflection = new \ReflectionClass(get_called_class());
        foreach ($reflection->getConstants() as $name => $value) {

            // if the current value is set in the allow-list, create new element in return array
            if (($this->allowList & $value) === $value) {
                $allowArray[$value] = $name;
            }
        }

        return $allowArray;
    }


    /**
     * @param int $value
     *
     * adds a value defined in the child class to the allow-list
     *
     * this function works using some ✨ bit-magic ✨.
     */
    public function allow(int $value): void
    {
        /*
         * first, check if the given value is greater than zero, no negative values can be added.
         * afterwards, check if the given value is actually a power of two or different said, if only one bit is set
         * this is accomplished by comparing the value with itself - 1 by an AND operator.
         * e.g. 1010 & (1010-1) == 1010 & 1001 = 1000 <-- there is more than 1 bit set in this example
         * e.g. 0100 & (0100-1) == 0100 & 0011 = 0000 <-- only 1 bit is set
         * at last, check if the given value is already present in the allow-list
         * e.g. setting 0100 twice results in 1000. this means, that now a different value is set,
         *      but the actual value is not anymore
         */
         if (($value > 0 && ($value & ($value - 1)) === 0) && (($this->allowList & $value) === 0)) {
            $this->allowList += $value;
        }
    }

    /**
     * @param int $value
     * @return bool
     *
     * this function is used to determine, if a certain value of the child class is listed in the allow-list
     */
    public function isAllowed(int $value): bool
    {
        return ($this->allowList & $value) === $value;
    }

    public function getIntegerValue() : int
    {
        return $this->allowList;
    }
}
