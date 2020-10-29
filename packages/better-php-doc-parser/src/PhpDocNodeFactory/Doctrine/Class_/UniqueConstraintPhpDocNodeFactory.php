<?php

declare(strict_types=1);

namespace Rector\BetterPhpDocParser\PhpDocNodeFactory\Doctrine\Class_;

use Doctrine\ORM\Mapping\UniqueConstraint;
use Nette\Utils\Strings;
use Rector\BetterPhpDocParser\Annotation\AnnotationItemsResolver;
use Rector\BetterPhpDocParser\PhpDocNode\Doctrine\Class_\UniqueConstraintTagValueNode;

final class UniqueConstraintPhpDocNodeFactory
{
    /**
     * @var string
     */
    private const UNIQUE_CONSTRAINT_REGEX = '#(?<tag>@(ORM\\\\)?UniqueConstraint)\((?<content>.*?)\),?#si';

    /**
     * @var AnnotationItemsResolver
     */
    private $annotationItemsResolver;

    public function __construct(AnnotationItemsResolver $annotationItemsResolver)
    {
        $this->annotationItemsResolver = $annotationItemsResolver;
    }

    /**
     * @param UniqueConstraint[]|null $uniqueConstraints
     * @return UniqueConstraintTagValueNode[]
     */
    public function createUniqueConstraintTagValueNodes(?array $uniqueConstraints, string $annotationContent): array
    {
        if ($uniqueConstraints === null) {
            return [];
        }
        foreach ($uniqueConstraints as $key => $uniqueConstraint) {
            $uniqueConstraintContents = Strings::matchAll($annotationContent, self::UNIQUE_CONSTRAINT_REGEX);
            $uniqueConstraintTagValueNodes = [];
            $subAnnotationContent = $uniqueConstraintContents[$key];

            $items = $this->annotationItemsResolver->resolve($uniqueConstraint);
            $uniqueConstraintTagValueNodes[] = new UniqueConstraintTagValueNode(
                $items,
                $subAnnotationContent['content'],
                $subAnnotationContent['tag']
            );
        }

        return $uniqueConstraintTagValueNodes;
    }
}
