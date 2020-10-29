<?php

declare(strict_types=1);

namespace Rector\NodeCollector\Reflection;

use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\Native\NativeMethodReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\Type;
use PHPStan\Type\TypeUtils;
use Rector\Core\Exception\ShouldNotHappenException;
use Rector\NodeNameResolver\NodeNameResolver;
use Rector\NodeTypeResolver\Node\AttributeKey;
use Rector\NodeTypeResolver\NodeTypeResolver;

final class MethodReflectionProvider
{
    /**
     * @var NodeTypeResolver
     */
    private $nodeTypeResolver;

    /**
     * @var ReflectionProvider
     */
    private $reflectionProvider;

    /**
     * @var NodeNameResolver
     */
    private $nodeNameResolver;

    public function __construct(
        NodeTypeResolver $nodeTypeResolver,
        NodeNameResolver $nodeNameResolver,
        ReflectionProvider $reflectionProvider
    ) {
        $this->nodeTypeResolver = $nodeTypeResolver;
        $this->reflectionProvider = $reflectionProvider;
        $this->nodeNameResolver = $nodeNameResolver;
    }

    /**
     * @return Type[]
     */
    public function provideParameterTypesFromMethodReflection(MethodReflection $methodReflection): array
    {
        if ($methodReflection instanceof NativeMethodReflection) {
            // method "getParameters()" does not exist there
            return [];
        }

        $parameterReflections = $this->getParameterReflectionsFromMethodReflection($methodReflection);
        foreach ($parameterReflections as $phpParameterReflection) {
            $parameterTypes = [];
            $parameterTypes[] = $phpParameterReflection->getType();
        }

        return $parameterTypes;
    }

    public function provideByClassAndMethodName(string $class, string $method, Scope $scope): ?MethodReflection
    {
        $classReflection = $this->reflectionProvider->getClass($class);
        if (! $classReflection->hasMethod($method)) {
            return null;
        }

        return $classReflection->getMethod($method, $scope);
    }

    /**
     * @return Type[]
     */
    public function provideParameterTypesByStaticCall(StaticCall $staticCall): array
    {
        $methodReflection = $this->provideByStaticCall($staticCall);
        if ($methodReflection === null) {
            return [];
        }

        return $this->provideParameterTypesFromMethodReflection($methodReflection);
    }

    public function provideByStaticCall(StaticCall $staticCall): ?MethodReflection
    {
        $objectType = $this->nodeTypeResolver->resolve($staticCall->class);

        $methodName = $this->nodeNameResolver->getName($staticCall->name);
        if ($methodName === null) {
            return null;
        }

        /** @var Scope|null $scope */
        $scope = $staticCall->getAttribute(AttributeKey::SCOPE);
        if (! $scope instanceof Scope) {
            throw new ShouldNotHappenException();
        }
        $classes = TypeUtils::getDirectClassNames($objectType);

        foreach ($classes as $class) {
            $methodReflection = $this->provideByClassAndMethodName($class, $methodName, $scope);
            if ($methodReflection instanceof MethodReflection) {
                return $methodReflection;
            }
        }

        return null;
    }

    /**
     * @return Type[]
     */
    public function provideParameterTypesByClassMethod(ClassMethod $classMethod): array
    {
        $methodReflection = $this->provideByClassMethod($classMethod);
        if ($methodReflection === null) {
            return [];
        }

        return $this->provideParameterTypesFromMethodReflection($methodReflection);
    }

    public function provideByClassMethod(ClassMethod $classMethod): ?MethodReflection
    {
        $class = $classMethod->getAttribute(AttributeKey::CLASS_NAME);
        if (! is_string($class)) {
            return null;
        }

        $method = $this->nodeNameResolver->getName($classMethod->name);
        if (! is_string($method)) {
            return null;
        }

        $scope = $classMethod->getAttribute(AttributeKey::SCOPE);
        if (! $scope instanceof Scope) {
            return null;
        }

        return $this->provideByClassAndMethodName($class, $method, $scope);
    }

    /**
     * @return ParameterReflection[]
     */
    public function getParameterReflectionsFromMethodReflection(MethodReflection $methodReflection): array
    {
        $methodReflectionVariant = ParametersAcceptorSelector::selectSingle($methodReflection->getVariants());
        return $methodReflectionVariant->getParameters();
    }
}
