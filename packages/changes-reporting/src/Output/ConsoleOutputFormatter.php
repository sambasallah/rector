<?php

declare(strict_types=1);

namespace Rector\ChangesReporting\Output;

use Nette\Utils\Strings;
use Rector\ChangesReporting\Application\ErrorAndDiffCollector;
use Rector\ChangesReporting\Contract\Output\OutputFormatterInterface;
use Rector\Core\Configuration\Configuration;
use Rector\Core\Configuration\Option;
use Rector\Core\PhpParser\Printer\BetterStandardPrinter;
use Rector\Core\ValueObject\Application\RectorError;
use Rector\Core\ValueObject\Reporting\FileDiff;
use Rector\NodeTypeResolver\Node\AttributeKey;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symplify\SmartFileSystem\SmartFileInfo;

final class ConsoleOutputFormatter implements OutputFormatterInterface
{
    /**
     * @var string
     */
    public const NAME = 'console';

    /**
     * @var string
     */
    private const ON_LINE_REGEX = '# on line #';

    /**
     * @var SymfonyStyle
     */
    private $symfonyStyle;

    /**
     * @var BetterStandardPrinter
     */
    private $betterStandardPrinter;

    /**
     * @var Configuration
     */
    private $configuration;

    public function __construct(
        BetterStandardPrinter $betterStandardPrinter,
        Configuration $configuration,
        SymfonyStyle $symfonyStyle
    ) {
        $this->symfonyStyle = $symfonyStyle;
        $this->betterStandardPrinter = $betterStandardPrinter;
        $this->configuration = $configuration;
    }

    public function report(ErrorAndDiffCollector $errorAndDiffCollector): void
    {
        if ($this->configuration->getOutputFile()) {
            $message = sprintf(
                'Option "--%s" can be used only with "--%s %s"',
                Option::OPTION_OUTPUT_FILE,
                Option::OPTION_OUTPUT_FORMAT,
                'json'
            );
            $this->symfonyStyle->error($message);
        }

        $this->reportFileDiffs($errorAndDiffCollector->getFileDiffs());
        $this->reportErrors($errorAndDiffCollector->getErrors());
        $this->reportRemovedFilesAndNodes($errorAndDiffCollector);

        if ($errorAndDiffCollector->getErrors() !== []) {
            return;
        }
        $message = 'Rector is done!';
        $changeCount = $errorAndDiffCollector->getFileDiffsCount()
                     + $errorAndDiffCollector->getRemovedAndAddedFilesCount();
        if ($changeCount > 0) {
            $message .= sprintf(
                ' %d file%s %s.',
                $changeCount,
                $changeCount > 1 ? 's' : '',
                $this->configuration->isDryRun() ? 'would have changed (dry-run)' : ($changeCount === 1 ? 'has' : 'have') . ' been changed'
            );
        }

        $this->symfonyStyle->success($message);
    }

    public function getName(): string
    {
        return self::NAME;
    }

    /**
     * @param FileDiff[] $fileDiffs
     */
    private function reportFileDiffs(array $fileDiffs): void
    {
        if (count($fileDiffs) <= 0) {
            return;
        }

        // normalize
        ksort($fileDiffs);
        $message = sprintf('%d file%s with changes', count($fileDiffs), count($fileDiffs) === 1 ? '' : 's');

        $this->symfonyStyle->title($message);
        foreach ($fileDiffs as $fileDiff) {
            $i = 0;
            $relativeFilePath = $fileDiff->getRelativeFilePath();
            $message = sprintf('<options=bold>%d) %s</>', ++$i, $relativeFilePath);

            $this->symfonyStyle->writeln($message);
            $this->symfonyStyle->newLine();
            $this->symfonyStyle->writeln($fileDiff->getDiffConsoleFormatted());
            $this->symfonyStyle->newLine();

            if ($fileDiff->getRectorChanges() !== []) {
                $this->symfonyStyle->writeln('<options=underscore>Applied rules:</>');
                $this->symfonyStyle->newLine();
                $this->symfonyStyle->listing($fileDiff->getRectorClasses());
                $this->symfonyStyle->newLine();
            }
        }
    }

    /**
     * @param RectorError[] $errors
     */
    private function reportErrors(array $errors): void
    {
        foreach ($errors as $error) {
            $errorMessage = $error->getMessage();
            $errorMessage = $this->normalizePathsToRelativeWithLine($errorMessage);

            if ($error->getLine()) {
                $message = sprintf(
                    'Could not process "%s" file%s, due to: %s"%s".',
                    $error->getFileInfo()
                        ->getRelativeFilePathFromCwd(),
                    $error->getRectorClass() ? ' by "' . $error->getRectorClass() . '"' : '',
                    PHP_EOL,
                    $errorMessage
                );
                $message .= ' On line: ' . $error->getLine();
            }

            $this->symfonyStyle->error($message);
        }
    }

    private function reportRemovedFilesAndNodes(ErrorAndDiffCollector $errorAndDiffCollector): void
    {
        if ($errorAndDiffCollector->getRemovedAndAddedFilesCount() !== 0) {
            $message = sprintf('%d files were added/removed', $errorAndDiffCollector->getRemovedAndAddedFilesCount());
            $this->symfonyStyle->note($message);
        }

        $this->reportRemovedNodes($errorAndDiffCollector);
    }

    private function normalizePathsToRelativeWithLine(string $errorMessage): string
    {
        $errorMessage = Strings::replace($errorMessage, '#' . preg_quote(getcwd(), '#') . '/#');
        return $errorMessage = Strings::replace($errorMessage, self::ON_LINE_REGEX, ':');
    }

    private function reportRemovedNodes(ErrorAndDiffCollector $errorAndDiffCollector): void
    {
        if ($errorAndDiffCollector->getRemovedNodeCount() === 0) {
            return;
        }
        $message = sprintf('%d nodes were removed', $errorAndDiffCollector->getRemovedNodeCount());

        $this->symfonyStyle->warning($message);

        if ($this->symfonyStyle->isVeryVerbose()) {
            foreach ($errorAndDiffCollector->getRemovedNodes() as $removedNode) {
                $i = 0;
                /** @var SmartFileInfo $fileInfo */
                $fileInfo = $removedNode->getAttribute(AttributeKey::FILE_INFO);
                $message = sprintf(
                    '<options=bold>%d) %s:%d</>',
                    ++$i,
                    $fileInfo->getRelativeFilePath(),
                    $removedNode->getStartLine()
                );

                $this->symfonyStyle->writeln($message);

                $printedNode = $this->betterStandardPrinter->print($removedNode);

                // color red + prefix with "-" to visually demonstrate removal
                $printedNode = '-' . Strings::replace($printedNode, '#\n#', "\n-");
                $printedNode = $this->colorTextToRed($printedNode);

                $this->symfonyStyle->writeln($printedNode);
                $this->symfonyStyle->newLine(1);
            }
        }
    }

    private function colorTextToRed(string $text): string
    {
        return '<fg=red>' . $text . '</fg=red>';
    }
}
