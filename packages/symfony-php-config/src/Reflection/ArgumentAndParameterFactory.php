<?php

declare(strict_types=1);

namespace Rector\SymfonyPhpConfig\Reflection;

use Symplify\PackageBuilder\Reflection\PrivatesAccessor;

final class ArgumentAndParameterFactory
{
    /**
     * @var PrivatesAccessor
     */
    private $privatesAccessor;

    public function __construct()
    {
        $this->privatesAccessor = new PrivatesAccessor();
    }

    /**
     * @param array<string, mixed> $arguments
     * @param array<string, mixed> $properties
     */
    public function create(string $className, array $arguments, array $properties): object
    {
        foreach ($properties as $name => $value) {
            $object = new $className(...$arguments);
            $this->privatesAccessor->setPrivateProperty($object, $name, $value);
        }

        return $object;
    }
}
