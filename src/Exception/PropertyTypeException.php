<?php

namespace Wexample\SymfonyEntityTransfer\Exception;

use Wexample\SymfonyApi\Exception\DeserializationException;

/**
 * Exception thrown when a property type mismatch occurs during deserialization.
 */
class PropertyTypeException extends DeserializationException
{
    /**
     * The name of the property with the type mismatch.
     */
    private string $propertyName;

    /**
     * The expected type of the property.
     */
    private string $expectedType;

    /**
     * The actual type that was provided.
     */
    private string $actualType;

    /**
     * Creates a new property type exception.
     *
     * @param string $propertyName The name of the property with the type mismatch
     * @param string $expectedType The expected type of the property
     * @param string $actualType The actual type that was provided
     * @param \Throwable|null $previous The previous exception if nested
     */
    public function __construct(
        string $propertyName,
        string $expectedType,
        string $actualType,
        int $code = 0,
        ?string $internalCodeSuffix = self::CODE_TYPE_MISMATCH,
        ?\Throwable $previous = null
    ) {
        $this->propertyName = $propertyName;
        $this->expectedType = $expectedType;
        $this->actualType = $actualType;

        parent::__construct(
            previous: $previous,
            message: sprintf(
                "Type mismatch for property '%s': expected %s, got %s",
                $propertyName,
                $expectedType,
                $actualType
            ),
            code: $code,
            internalCodeSuffix: $internalCodeSuffix,
            context: [
                'propertyName' => $propertyName,
                'expectedType' => $expectedType,
                'actualType' => $actualType,
            ]
        );
    }

    /**
     * Gets the name of the property with the type mismatch.
     *
     * @return string The property name
     */
    public function getPropertyName(): string
    {
        return $this->propertyName;
    }

    /**
     * Gets the expected type of the property.
     *
     * @return string The expected type
     */
    public function getExpectedType(): string
    {
        return $this->expectedType;
    }

    /**
     * Gets the actual type that was provided.
     *
     * @return string The actual type
     */
    public function getActualType(): string
    {
        return $this->actualType;
    }
}
