<?php

declare(strict_types=1);

namespace PrismCms\Tests\database;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Test;
use PrismCms\Tests\TestCase;

#[CoversNothing]
class CreatePagesTableMigrationTest extends TestCase
{
    #[Test]
    public function it_leaves_an_existing_pages_table_alone(): void
    {
        Schema::drop('pages');
        Schema::create('pages', function (Blueprint $table): void {
            $table->id();
            $table->string('site_column');
        });
        $migration = require __DIR__.'/../../database/migrations/2026_11_25_000000_create_pages_table.php';

        $migration->up();

        $this->assertSame(['id', 'site_column'], Schema::getColumnListing('pages'));
    }
}
