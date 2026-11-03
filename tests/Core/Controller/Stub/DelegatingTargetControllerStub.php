<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Controller\Stub;

/**
 * Plain controller object delegated to by DelegatingController in
 * tests/Core/Controller/DelegatingControllerTest.php. Intentionally has no
 * `index()` method so DelegatingController's "fall back to renderView()"
 * branch can be exercised.
 */
class DelegatingTargetControllerStub
{
    public function save(string $id): string
    {
        return 'save:' . $id;
    }

    public function show(string $view): string
    {
        return $view;
    }
}
