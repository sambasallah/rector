<?php

declare(strict_types=1);

namespace Rector\NodeTypeResolver\PhpDocParser;

use PhpParser\Node;
use PHPStan\Analyser\NameScope;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Type\ClassStringType;
use PHPStan\Type\IterableType;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StaticType;
use PHPStan\Type\Type;
use Rector\AttributeAwarePhpDoc\Ast\Type\AttributeAwareIdentifierTypeNode;
use Rector\BetterPhpDocParser\Type\PreSlashStringType;
use Rector\NodeTypeResolver\Contract\PhpDocParser\PhpDocTypeMapperInterface;
use Rector\NodeTypeResolver\Node\AttributeKey;
use Rector\NodeTypeResolver\TypeMapper\ScalarStringToTypeMapper;
use Rector\PHPStan\Type\ParentStaticType;
use Rector\PHPStan\Type\SelfObjectType;
use Rector\TypeDeclaration\PHPStan\Type\ObjectTypeSpecifier;

final class IdentifierTypeMapper implements PhpDocTypeMapperInterface
{
    /**
     * @var ScalarStringToTypeMapper
     */
    private $scalarStringToTypeMapper;

    /**
     * @var ObjectTypeSpecifier
     */
    private $objectTypeSpecifier;

    public function __construct(
        ScalarStringToTypeMapper $scalarStringToTypeMapper,
        ObjectTypeSpecifier $objectTypeSpecifier
    ) {
        $this->scalarStringToTypeMapper = $scalarStringToTypeMapper;
        $this->objectTypeSpecifier = $objectTypeSpecifier;
    }

    public function getNodeType(): string
    {
        return IdentifierTypeNode::class;
    }

    /**
     * @param AttributeAwareIdentifierTypeNode&IdentifierTypeNode $typeNode
     */
    public function mapToPHPStanType(TypeNode $typeNode, Node $node, NameScope $nameScope): Type
    {
        $type = $this->scalarStringToTypeMapper->mapScalarStringToType($typeNode->name);
        if (! $type instanceof MixedType) {
            return $type;
        }

        $loweredName = strtolower($typeNode->name);

        // @todo for all scalars
        if ($loweredName === '\string') {
            return new PreSlashStringType();
        }

        if ($loweredName === 'class-string') {
            return new ClassStringType();
        }

        if ($loweredName === 'self') {
            return $this->mapSelf($node);
        }

        if ($loweredName === 'parent') {
            return $this->mapParent($node);
        }

        if ($loweredName === 'static') {
            return $this->mapStatic($node);
        }

        if ($loweredName === 'iterable') {
            return new IterableType(new MixedType(), new MixedType());
        }

        // @todo improve - making many false positives now
        $objectType = new ObjectType($typeNode->name);

        return $this->objectTypeSpecifier->narrowToFullyQualifiedOrAlaisedObjectType($node, $objectType);
    }

    private function mapStatic(Node $node): Type
    {
        /** @var string|null $className */
        $className = $node->getAttribute(AttributeKey::CLASS_NAME);
        if ($className === null) {
            return new MixedType();
        }

        return new StaticType($className);
    }

    private function mapParent(Node $node): Type
    {
        /** @var string|null $parentClassName */
        $parentClassName = $node->getAttribute(AttributeKey::PARENT_CLASS_NAME);
        if ($parentClassName === null) {
            return new MixedType();
        }

        return new ParentStaticType($parentClassName);
    }

    private function mapSelf(Node $node): Type
    {
        /** @var string|null $className */
        $className = $node->getAttribute(AttributeKey::CLASS_NAME);
        if ($className === null) {
            // self outside the class, e.g. in a function
            return new MixedType();
        }

        return new SelfObjectType($className);
    }
}