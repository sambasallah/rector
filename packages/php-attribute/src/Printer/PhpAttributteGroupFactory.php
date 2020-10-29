<?php

declare(strict_types=1);

namespace Rector\PhpAttribute\Printer;

use PhpParser\BuilderHelpers;
use PhpParser\Node\Arg;
use PhpParser\Node\Attribute;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use Rector\BetterPhpDocParser\Contract\PhpDocNode\SilentKeyNodeInterface;
use Rector\PhpAttribute\Contract\ManyPhpAttributableTagNodeInterface;
use Rector\PhpAttribute\Contract\PhpAttributableTagNodeInterface;

final class PhpAttributteGroupFactory
{
    /**
     * @var string
     */
    public const TBA = 'TBA';

    /**
     * @param PhpAttributableTagNodeInterface[] $phpAttributableTagNodes
     * @return AttributeGroup[]
     */
    public function create(array $phpAttributableTagNodes): array
    {
        foreach ($phpAttributableTagNodes as $phpAttributableTagNode) {
            $attributeGroups = [];
            $currentAttributeGroups = $this->printPhpAttributableTagNode($phpAttributableTagNode);
            $attributeGroups = array_merge($attributeGroups, $currentAttributeGroups);
        }

        return $attributeGroups;
    }

    /**
     * @return Arg[]
     */
    public function printItemsToAttributeArgs(PhpAttributableTagNodeInterface $phpAttributableTagNode): array
    {
        if ($phpAttributableTagNode instanceof SilentKeyNodeInterface) {
            $silentKey = null;
            $silentKey = $phpAttributableTagNode->getSilentKey();
        }
        $items = $phpAttributableTagNode->getAttributableItems();

        return $this->createArgsFromItems($items, $silentKey);
    }

    /**
     * @return AttributeGroup[]
     */
    private function printPhpAttributableTagNode(PhpAttributableTagNodeInterface $phpAttributableTagNode): array
    {
        $args = $this->printItemsToAttributeArgs($phpAttributableTagNode);

        $attributeClassName = $this->resolveAttributeClassName($phpAttributableTagNode);

        $attributeGroups = [];
        $attributeGroups[] = $this->createAttributeGroupFromNameAndArgs($attributeClassName, $args);

        if ($phpAttributableTagNode instanceof ManyPhpAttributableTagNodeInterface) {
            foreach ($phpAttributableTagNode->provide() as $shortName => $items) {
                $args = $this->createArgsFromItems($items);
                $name = new Name($shortName);
                $attributeGroups[] = $this->createAttributeGroupFromNameAndArgs($name, $args);
            }
        }

        return $attributeGroups;
    }

    /**
     * @param mixed[] $items
     * @return Arg[]
     */
    private function createArgsFromItems(array $items, ?string $silentKey = null): array
    {
        if ($silentKey !== null && isset($items[$silentKey])) {
            $args = [];
            $silentValue = BuilderHelpers::normalizeValue($items[$silentKey]);
            $args[] = new Arg($silentValue);
            unset($items[$silentKey]);
        }

        if ($this->isArrayArguments($items)) {
            foreach ($items as $key => $value) {
                $value = BuilderHelpers::normalizeValue($value);
                $argumentName = new Identifier($key);
                $args[] = new Arg($value, false, false, [], $argumentName);
            }
        } else {
            foreach ($items as $value) {
                $value = BuilderHelpers::normalizeValue($value);
                $args[] = new Arg($value);
            }
        }

        return $args;
    }

    private function resolveAttributeClassName(PhpAttributableTagNodeInterface $phpAttributableTagNode): Name
    {
        if ($phpAttributableTagNode->getAttributeClassName() !== self::TBA) {
            return new FullyQualified($phpAttributableTagNode->getAttributeClassName());
        }

        return new Name($phpAttributableTagNode->getShortName());
    }

    /**
     * @param Arg[] $args
     */
    private function createAttributeGroupFromNameAndArgs(Name $name, array $args): AttributeGroup
    {
        $attribute = new Attribute($name, $args);
        return new AttributeGroup([$attribute]);
    }

    /**
     * @param mixed[] $items
     */
    private function isArrayArguments(array $items): bool
    {
        foreach (array_keys($items) as $key) {
            if (! is_int($key)) {
                return true;
            }
        }

        return false;
    }
}
