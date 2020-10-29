<?php

declare(strict_types=1);

namespace Rector\NodeNameResolver\NodeNameResolver;

use PhpParser\Node;
use PhpParser\Node\Stmt\Function_;
use Rector\NodeNameResolver\Contract\NodeNameResolverInterface;
use Rector\NodeTypeResolver\Node\AttributeKey;

final class FunctionNameResolver implements NodeNameResolverInterface
{
    public function getNode(): string
    {
        return Function_::class;
    }

    /**
     * @param Function_ $node
     */
    public function resolve(Node $node): ?string
    {
        $namespaceName = $node->getAttribute(AttributeKey::NAMESPACE_NAME);

        if ($namespaceName) {
            $bareName = (string) $node->name;
            return $namespaceName . '\\' . $bareName;
        }

        return $bareName;
    }
}
