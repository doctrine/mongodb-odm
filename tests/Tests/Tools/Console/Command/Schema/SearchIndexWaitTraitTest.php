<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Tools\Console\Command\Schema;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadataFactoryInterface;
use Doctrine\ODM\MongoDB\Tools\Console\Command\Schema\SearchIndexWaitTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\StringInput;

use function escapeshellarg;

class SearchIndexWaitTraitTest extends TestCase
{
    private object $subject;

    public function setUp(): void
    {
        parent::setUp();

        $metadataFactory = $this->createStub(ClassMetadataFactoryInterface::class);

        $this->subject = new class ($metadataFactory) {
            use SearchIndexWaitTrait;

            public function __construct(private ClassMetadataFactoryInterface $metadataFactory)
            {
            }

            public function getWaitTimeMs(StringInput $input): ?int
            {
                return $this->getWaitTimeMsFromInput($input);
            }

            protected function getMetadataFactory(): ClassMetadataFactoryInterface
            {
                return $this->metadataFactory;
            }
        };
    }

    public function testWaitOptionNotPassed(): void
    {
        self::assertNull($this->subject->getWaitTimeMs($this->createInput('')));
    }

    public function testWaitOptionWithoutValueUsesDefaultTimeout(): void
    {
        self::assertSame(300_000, $this->subject->getWaitTimeMs($this->createInput('--wait')));
    }

    #[DataProvider('provideValidWaitValues')]
    public function testWaitOptionWithValidValue(string $value, int $expectedMs): void
    {
        self::assertSame($expectedMs, $this->subject->getWaitTimeMs($this->createInput('--wait=' . escapeshellarg($value))));
    }

    /** @return array<string, array{string, int}> */
    public static function provideValidWaitValues(): array
    {
        return [
            'milliseconds' => ['5000', 5000],
            'seconds duration' => ['30 seconds', 30_000],
            'minute duration without space' => ['1minute', 60_000],
            'hour duration' => ['1 hour', 3_600_000],
        ];
    }

    #[DataProvider('provideInvalidWaitValues')]
    public function testWaitOptionWithInvalidValueThrows(string $value): void
    {
        $this->expectException(InvalidOptionException::class);

        $this->subject->getWaitTimeMs($this->createInput('--wait=' . $value));
    }

    /** @return array<string, array{string}> */
    public static function provideInvalidWaitValues(): array
    {
        return [
            'zero' => ['0'],
            'negative number' => ['-100'],
            'not a duration' => ['not-a-duration'],
        ];
    }

    private function createInput(string $tokens): StringInput
    {
        $input = new StringInput($tokens);
        $input->bind(new InputDefinition([
            new InputOption('wait', null, InputOption::VALUE_OPTIONAL, '', false),
        ]));

        return $input;
    }
}
