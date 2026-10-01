<?php

namespace LegacyOpenCart\Tests;

use PHPUnit\Framework\TestCase;

final class ScocUtilityTest extends TestCase
{
    public function testCleanStringReplacesHtmlEntitiesAndRemovesLineBreaks(): void
    {
        $actual = \scoc_utility::cleanString("Hello\r\n&ldquo;World&rdquo;\r\n");

        $this->assertStringContainsString('Hello', $actual);
        $this->assertStringContainsString('&#x93;World&#x94;', $actual);
        $this->assertStringNotContainsString("\r", $actual);
        $this->assertStringNotContainsString("\n", $actual);
    }

    public function testCleanNameReplacesEntityVariants(): void
    {
        $actual = \scoc_utility::cleanName('Hello &ndash; World');

        $this->assertStringContainsString('Hello', $actual);
        $this->assertStringContainsString('&#x97;', $actual);
        $this->assertStringContainsString('World', $actual);
    }
}
