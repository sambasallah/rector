<?php

declare(strict_types=1);

namespace Rector\DynamicTypeAnalysis\Rector\ClassMethod;

use PhpParser\Node;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Param;
use PhpParser\Node\Scalar\LNumber;
use PhpParser\Node\Scalar\MagicConst\Method;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use Rector\Core\RectorDefinition\CodeSample;
use Rector\Core\RectorDefinition\RectorDefinition;
use Rector\DynamicTypeAnalysis\Probe\TypeStaticProbe;

/**
 * @see \Rector\DynamicTypeAnalysis\Tests\Rector\ClassMethod\DecorateMethodWithArgumentTypeProbeRector\DecorateMethodWithArgumentTypeProbeRectorTest
 */
final class DecorateMethodWithArgumentTypeProbeRector extends AbstractArgumentProbeRector
{
    public function getDefinition(): RectorDefinition
    {
        return new RectorDefinition('Add probe that records argument types to each method', [
            new CodeSample(
                <<<'CODE_SAMPLE'
class SomeClass
{
    public function run($arg)
    {
    }
}
CODE_SAMPLE
,
                <<<'CODE_SAMPLE'
class SomeClass
{
    public function run($arg)
    {
        \Rector\DynamicTypeAnalysis\Probe\TypeStaticProbe::recordArgumentType($arg, __METHOD__, 0);
    }
}
CODE_SAMPLE
            ),
        ]);
    }

    /**
     * @return string[]
     */
    public function getNodeTypes(): array
    {
        return [ClassMethod::class];
    }

    /**
     * @param ClassMethod $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($this->shouldSkipClassMethod($node)) {
            return null;
        }

        $probeStaticCalls = $this->createRecordArgumentTypeStaticCalls($node);
        $node->stmts = array_merge($probeStaticCalls, (array) $node->stmts);

        return $node;
    }

    /**
     * @return Expression[]
     */
    private function createRecordArgumentTypeStaticCalls(ClassMethod $classMethod): array
    {
        foreach ($classMethod->params as $i => $param) {
            $probeStaticCalls = [];
            $probeStaticCall = $this->createFromVariableAndPosition($param, $i);
            $probeStaticCalls[] = new Expression($probeStaticCall);
        }

        return $probeStaticCalls;
    }

    private function createFromVariableAndPosition(Param $param, int $i): StaticCall
    {
        return $this->createStaticCall(TypeStaticProbe::class, 'recordArgumentType', [
            $param->var,
            new Method(),
            new LNumber($i),
        ]);
    }
}
