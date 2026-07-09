<?php

declare(strict_types=1);

namespace Tests\Unit\Linter\Visitor;

use DouglasGreen\PhpLinter\IssueHolder;
use DouglasGreen\PhpLinter\Linter\Visitor\FunctionVisitor;
use PhpParser\Node\Expr\Variable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(FunctionVisitor::class)]
#[Small]
final class FunctionVisitorTest extends TestCase
{
    private IssueHolder $issueHolder;

    protected function setUp(): void
    {
        $this->issueHolder = new IssueHolder();
    }

    #[Test]
    public function testItCreatesFunctionVisitorWithParameters(): void
    {
        // Arrange
        $params = [
            'param1' => ['type' => 'string', 'promoted' => false],
            'param2' => ['type' => null, 'promoted' => true],
        ];
        // Act
        $visitor = new FunctionVisitor($this->issueHolder, 'testFunction', [], $params);
        // Assert
        $this->assertSame($params, $visitor->getParams());
    }
}
