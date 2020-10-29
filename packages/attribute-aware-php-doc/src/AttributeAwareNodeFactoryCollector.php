<?php

declare(strict_types=1);

namespace Rector\AttributeAwarePhpDoc;

use Rector\AttributeAwarePhpDoc\Contract\AttributeNodeAwareFactory\AttributeNodeAwareFactoryInterface;

final class AttributeAwareNodeFactoryCollector
{
    /**
     * @var AttributeNodeAwareFactoryInterface[]
     */
    private $attributeAwareNodeFactories = [];

    /**
     * @param AttributeNodeAwareFactoryInterface[] $attributeAwareNodeFactories
     */
    public function __construct(array $attributeAwareNodeFactories)
    {
        $this->attributeAwareNodeFactories = $attributeAwareNodeFactories;
    }

    /**
     * @return AttributeNodeAwareFactoryInterface[]
     */
    public function provide(): array
    {
        return $this->attributeAwareNodeFactories;
    }

    /**
     * @return string[]
     */
    public function getSupportedNodeClasses(): array
    {
        foreach ($this->attributeAwareNodeFactories as $attributeAwareNodeFactory) {
            $supportedNodeClasses = [];
            $supportedNodeClasses[] = $attributeAwareNodeFactory->getOriginalNodeClass();
        }

        return $supportedNodeClasses;
    }
}
