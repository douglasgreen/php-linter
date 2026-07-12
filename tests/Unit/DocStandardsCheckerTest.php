<?php

declare(strict_types=1);

namespace Tests\Unit;

use DouglasGreen\PhpLinter\DocStandardsChecker;
use DouglasGreen\PhpLinter\IgnoreList;
use DouglasGreen\PhpLinter\IssueHolder;
use DouglasGreen\PhpLinter\Repository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(DocStandardsChecker::class)]
#[Small]
final class DocStandardsCheckerTest extends TestCase
{
    /**
     * @return iterable<string, array{0: string, 1: string}>
     */
    public static function licenseFileProvider(): iterable
    {
        yield 'LICENSE.md' => ['LICENSE.md', 'Legal terms'];
        yield 'LICENSE' => ['LICENSE', 'Legal terms'];
        yield 'LICENSE.txt' => ['LICENSE.txt', 'Legal terms'];
    }

    /**
     * @return iterable<string, array{0: string, 1: string}>
     */
    public static function agentInstructionFileProvider(): iterable
    {
        yield 'AGENTS.md' => ['AGENTS.md', 'Project instructions for coding agents'];
        yield 'CLAUDE.md' => ['CLAUDE.md', 'Project instructions for coding agents'];
    }

    #[Test]
    #[DataProvider('licenseFileProvider')]
    public function testItAcceptsLicenseFileAlternatives(string $licenseFile, string $description): void
    {
        $issues = $this->runChecker(['README.md', 'AGENTS.md', $licenseFile]);

        $this->assertArrayNotHasKey('Missing required file: ' . $description, $issues);
    }

    #[Test]
    #[DataProvider('agentInstructionFileProvider')]
    public function testItAcceptsAgentInstructionFileAlternatives(string $agentFile, string $description): void
    {
        $issues = $this->runChecker(['README.md', 'LICENSE', $agentFile]);

        $this->assertArrayNotHasKey('Missing required file: ' . $description, $issues);
    }

    /**
     * @param list<string> $files
     *
     * @return array<string, bool>
     */
    private function runChecker(array $files): array
    {
        $repository = $this->createStub(Repository::class);
        $repository->method('getAllFiles')->willReturn($files);
        $issueHolder = new IssueHolder();

        $checker = new DocStandardsChecker(
            dirname(__DIR__, 2),
            $issueHolder,
            new IgnoreList(dirname(__DIR__, 2)),
            $repository,
        );

        $checker->run();

        return $issueHolder->getIssues();
    }
}
