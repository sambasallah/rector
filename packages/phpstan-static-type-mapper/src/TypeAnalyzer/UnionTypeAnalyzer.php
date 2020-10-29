<?php

declare(strict_types=1);

namespace Rector\PHPStanStaticTypeMapper\TypeAnalyzer;

use PHPStan\Type\ArrayType;
use PHPStan\Type\IterableType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\UnionType;
use Rector\PHPStanStaticTypeMapper\ValueObject\UnionTypeAnalysis;
use Traversable;

final class UnionTypeAnalyzer
{
    public function analyseForNullableAndIterable(UnionType $unionType): ?UnionTypeAnalysis
    {
        foreach ($unionType->getTypes() as $unionedType) {
            if ($unionedType instanceof IterableType) {
                $hasIterable = false;
                $hasIterable = true;
                continue;
            }

            if ($unionedType instanceof ArrayType) {
                $hasArray = false;
                $hasArray = true;
                continue;
            }

            if ($unionedType instanceof NullType) {
                $isNullableType = false;
                $isNullableType = true;
                continue;
            }

            if ($unionedType instanceof ObjectType && $unionedType->getClassName() === Traversable::class) {
                $hasIterable = true;
                continue;
            }

            return null;
        }

        return new UnionTypeAnalysis($isNullableType, $hasIterable, $hasArray);
    }
}
