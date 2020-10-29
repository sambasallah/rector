<?php

declare(strict_types=1);

namespace Rector\BetterPhpDocParser\ValueObjectFactory;

use Nette\Utils\Strings;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagValueNode;
use Rector\BetterPhpDocParser\Contract\Doctrine\DoctrineTagNodeInterface;
use Rector\BetterPhpDocParser\Contract\PhpDocNode\SilentKeyNodeInterface;
use Rector\BetterPhpDocParser\PhpDocNode\Sensio\SensioRouteTagValueNode;
use Rector\BetterPhpDocParser\PhpDocNode\Symfony\SymfonyRouteTagValueNode;
use Rector\BetterPhpDocParser\Utils\ArrayItemStaticHelper;
use Rector\BetterPhpDocParser\ValueObject\TagValueNodeConfiguration;

/**
 * @see \Rector\BetterPhpDocParser\Tests\ValueObjectFactory\TagValueNodeConfigurationFactoryTest
 */
final class TagValueNodeConfigurationFactory
{
    /**
     * @var string
     */
    public const NEWLINE_AFTER_OPENING_REGEX = '#^(\(\s+|\n)#m';

    /**
     * @var string
     */
    public const NEWLINE_BEFORE_CLOSING_REGEX = '#(\s+\)|\n(\s+)?)$#m';

    /**
     * @var string
     */
    public const OPENING_BRACKET_REGEX = '#^\(#';

    /**
     * @var string
     */
    public const CLOSING_BRACKET_REGEX = '#\)$#';

    public function createFromOriginalContent(
        ?string $originalContent,
        PhpDocTagValueNode $phpDocTagValueNode
    ): TagValueNodeConfiguration {
        if ($originalContent === null) {
            return new TagValueNodeConfiguration();
        }

        $silentKey = $this->resolveSilentKey($phpDocTagValueNode);
        $orderedVisibleItems = ArrayItemStaticHelper::resolveAnnotationItemsOrder($originalContent, $silentKey);
        foreach ($orderedVisibleItems as $orderedVisibleItem) {
            $keysByQuotedStatus = [];
            $keysByQuotedStatus[$orderedVisibleItem] = $this->isKeyQuoted(
                $originalContent,
                $orderedVisibleItem,
                $silentKey
            );
        }

        $hasNewlineAfterOpening = (bool) Strings::match($originalContent, self::NEWLINE_AFTER_OPENING_REGEX);
        $hasNewlineBeforeClosing = (bool) Strings::match($originalContent, self::NEWLINE_BEFORE_CLOSING_REGEX);

        $hasOpeningBracket = (bool) Strings::match($originalContent, self::OPENING_BRACKET_REGEX);
        $hasClosingBracket = (bool) Strings::match($originalContent, self::CLOSING_BRACKET_REGEX);

        $isSilentKeyExplicit = (bool) Strings::contains($originalContent, sprintf('%s=', $silentKey));

        $arrayEqualSign = $this->resolveArrayEqualSignByPhpNodeClass($phpDocTagValueNode);

        return new TagValueNodeConfiguration(
            $originalContent,
            $orderedVisibleItems,
            $hasNewlineAfterOpening,
            $hasNewlineBeforeClosing,
            $hasOpeningBracket,
            $hasClosingBracket,
            $keysByQuotedStatus,
            $silentKey,
            $isSilentKeyExplicit,
            $arrayEqualSign
        );
    }

    private function resolveSilentKey(PhpDocTagValueNode $phpDocTagValueNode): ?string
    {
        if ($phpDocTagValueNode instanceof SilentKeyNodeInterface) {
            return $phpDocTagValueNode->getSilentKey();
        }

        return null;
    }

    private function isKeyQuoted(string $originalContent, string $key, ?string $silentKey): bool
    {
        $escapedKey = preg_quote($key, '#');

        $quotedKeyPattern = $this->createQuotedKeyPattern($silentKey, $key, $escapedKey);
        if ((bool) Strings::match($originalContent, $quotedKeyPattern)) {
            return true;
        }

        // @see https://regex101.com/r/VgvK8C/5/
        $quotedArrayPattern = sprintf('#%s=\{"(.*)"\}|\{"(.*)"\}#', $escapedKey);

        return (bool) Strings::match($originalContent, $quotedArrayPattern);
    }

    /**
     * Before:
     * (options={"key":"value"})
     *
     * After:
     * (options={"key"="value"})
     *
     * @see regex https://regex101.com/r/XfKi4A/1/
     *
     * @see https://github.com/rectorphp/rector/issues/3225
     * @see https://github.com/rectorphp/rector/pull/3241
     */
    private function resolveArrayEqualSignByPhpNodeClass(PhpDocTagValueNode $phpDocTagValueNode): string
    {
        if ($phpDocTagValueNode instanceof SymfonyRouteTagValueNode) {
            return '=';
        }

        if ($phpDocTagValueNode instanceof DoctrineTagNodeInterface) {
            return '=';
        }

        if ($phpDocTagValueNode instanceof SensioRouteTagValueNode) {
            return '=';
        }

        return ':';
    }

    private function createQuotedKeyPattern(?string $silentKey, string $key, string $escapedKey): string
    {
        if ($silentKey === $key) {
            // @see https://regex101.com/r/VgvK8C/4/
            return sprintf('#(%s=")|\("#', $escapedKey);
        }

        // @see https://regex101.com/r/VgvK8C/3/
        return sprintf('#%s="#', $escapedKey);
    }
}
