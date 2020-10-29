<?php

declare(strict_types=1);

namespace Rector\DocumentationGenerator;

use Nette\Utils\Strings;

final class RectorMetadataResolver
{
    public function resolvePackageFromRectorClass(string $rectorClass): string
    {
        // basic Rectors
        if (Strings::startsWith($rectorClass, 'Rector\Rector\\')) {
            return 'Core';
        }
        $rectorClassParts = explode('\\', $rectorClass);

        // Rector/<PackageGroup>/Rector/SomeRector
        return $rectorClassParts[1];
    }
}
