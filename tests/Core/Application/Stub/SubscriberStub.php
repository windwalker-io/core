<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Application\Stub;

/**
 * Plain subscriber used to exercise ApplicationTrait::handleListener()'s
 * numeric-key "plain class name" branch and the scalar-subscriber else branch.
 * No listener methods are required for dispatcher->subscribe()/->resolve() to succeed.
 */
class SubscriberStub {}
