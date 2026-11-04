<?php

declare(strict_types=1);

namespace Windwalker\Tests\Core\Task;

use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Windwalker\Core\Application\ApplicationInterface;
use Windwalker\Core\Task\TaskPayload;
use Windwalker\Core\Testing\Stub\ApplicationTraitStub;
use Windwalker\Tests\Core\Task\Stub\DeserializationProbe;
use Windwalker\Tests\Core\Task\Stub\SerializedTask;

class TaskPayloadTest extends TestCase
{
    #[Test]
    public function rejectsNonCallableObjectsBeforeEncoding(): void
    {
        $codec = new TaskPayload(require __DIR__ . '/Stub/application.php');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIs('Task must be a Closure or an object with a public __invoke() method.');
        $codec->encode(new \stdClass());
    }

    #[Test]
    public function usesTheTaskSpecificDerivedSecret(): void
    {
        $app = $this->createMock(ApplicationInterface::class);
        $app->expects($this->exactly(2))->method('getSecret')->willReturnCallback(
            static fn(?string $usage = null): string => match ($usage) {
                null => 'root-test-key',
                'task.run' => 'derived-test-key',
            }
        );
        $body = 'v1.' . base64_encode(serialize(new SerializedTask('value')));

        self::assertSame(
            $body . '.' . hash_hmac('sha256', $body, 'derived-test-key'),
            new TaskPayload($app)->encode(new SerializedTask('value'))
        );
    }

    #[Test]
    public function validatesBeforeRunningObjectHooks(): void
    {
        $codec = new TaskPayload(require __DIR__ . '/Stub/application.php');
        $payload = $codec->encode(new DeserializationProbe());
        DeserializationProbe::$restored = false;
        $parts = explode('.', $payload);
        $parts[2] = str_repeat('0', 64);

        try {
            $codec->decode(implode('.', $parts));
            self::fail('An invalid signature must be rejected.');
        } catch (InvalidArgumentException $e) {
            self::assertSame('Task payload signature is invalid.', $e->getMessage());
            self::assertFalse(DeserializationProbe::$restored);
        }

        self::assertSame('probe', $codec->decode($payload)());
        self::assertTrue(DeserializationProbe::$restored);
        DeserializationProbe::$restored = false;
    }

    #[Test]
    public function rejectsModifiedSerializedData(): void
    {
        $codec = new TaskPayload(require __DIR__ . '/Stub/application.php');
        $parts = explode('.', $codec->encode(new SerializedTask('original')));
        $parts[1] = base64_encode(serialize(new SerializedTask('modified')));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIs('Task payload signature is invalid.');
        $codec->decode(implode('.', $parts));
    }

    #[Test]
    public function rejectsAnotherApplicationSecret(): void
    {
        $sender = require __DIR__ . '/Stub/application.php';
        $receiver = require __DIR__ . '/Stub/application.php';
        $receiver->getContainer()->getParameters()->set('app', ['secret' => 'different-test-key']);
        $payload = new TaskPayload($sender)->encode(new SerializedTask());

        $this->expectException(InvalidArgumentException::class);
        new TaskPayload($receiver)->decode($payload);
    }

    #[Test]
    #[TestWith([''])]
    #[TestWith(['unsigned'])]
    #[TestWith(['v2.body.0000'])]
    #[TestWith(['v1..0000'])]
    #[TestWith(['v1.body.invalid'])]
    #[TestWith(['v1.body.' . '0000000000000000000000000000000000000000000000000000000000000000' . '.extra'])]
    public function rejectsMalformedEnvelopes(string $payload): void
    {
        $codec = new TaskPayload(require __DIR__ . '/Stub/application.php');

        $this->expectException(InvalidArgumentException::class);
        $codec->decode($payload);
    }

    #[Test]
    #[TestWith(['encode'])]
    #[TestWith(['decode'])]
    public function rejectsMissingApplicationSecret(string $operation): void
    {
        $codec = new TaskPayload(new ApplicationTraitStub());
        $signed = new TaskPayload(require __DIR__ . '/Stub/application.php')->encode(new SerializedTask());

        $this->expectException(LogicException::class);
        $this->expectExceptionMessageIs('An application secret is required to sign and verify task payloads.');

        if ($operation === 'encode') {
            $codec->encode(new SerializedTask());
        } else {
            $codec->decode($signed);
        }
    }

    #[Test]
    #[TestWith(['%%%'])]
    #[TestWith([''])]
    public function rejectsAuthenticatedInvalidBase64(string $encoded): void
    {
        $app = require __DIR__ . '/Stub/application.php';
        $body = 'v1.' . $encoded;
        $payload = $body . '.' . hash_hmac('sha256', $body, $app->getSecret('task.run'));

        $this->expectException(InvalidArgumentException::class);
        new TaskPayload($app)->decode($payload);
    }

    #[Test]
    #[TestWith(['broken'])]
    #[TestWith(['O:8:"stdClass":0:{}'])]
    #[TestWith(['s:6:"strlen";'])]
    #[TestWith(['a:2:{i:0;s:8:"DateTime";i:1;s:15:"createFromFormat";}'])]
    public function rejectsAuthenticatedInvalidSerializedTasks(string $serialized): void
    {
        $app = require __DIR__ . '/Stub/application.php';
        $body = 'v1.' . base64_encode($serialized);
        $payload = $body . '.' . hash_hmac('sha256', $body, $app->getSecret('task.run'));

        $this->expectException(\Throwable::class);
        new TaskPayload($app)->decode($payload);
    }
}
