<?php

declare(strict_types=1);

namespace Driver\Tests\Unit\Engines\MySql\Export;

use Driver\Engines\ConnectionInterface;
use Driver\Engines\MySql\Export\CommandAssembler;
use Driver\Engines\MySql\Export\TablesProvider;
use Driver\Pipeline\Environment\EnvironmentInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CommandAssemblerTest extends TestCase
{
    private CommandAssembler $commandAssembler;

    /** @var TablesProvider&MockObject */
    private MockObject $tablesProviderMock;

    /** @var ConnectionInterface&MockObject */
    private MockObject $connectionMock;

    /** @var EnvironmentInterface&MockObject */
    private MockObject $environmentMock;

    public function setUp(): void
    {
        $this->tablesProviderMock = $this->getMockBuilder(TablesProvider::class)
            ->disableOriginalConstructor()->getMock();
        $this->connectionMock = $this->getMockBuilder(ConnectionInterface::class)->getMockForAbstractClass();
        $this->connectionMock->expects($this->any())->method('getUser')->willReturn('user');
        $this->connectionMock->expects($this->any())->method('getPassword')->willReturn('password');
        $this->connectionMock->expects($this->any())->method('getHost')->willReturn('host');
        $this->connectionMock->expects($this->any())->method('getDatabase')->willReturn('db');
        $this->environmentMock = $this->getMockBuilder(EnvironmentInterface::class)->getMockForAbstractClass();
        $this->commandAssembler = new CommandAssembler($this->tablesProviderMock);
    }

    public function testReturnsEmptyArrayIfNoTables(): void
    {
        $this->tablesProviderMock->expects($this->any())->method('getAllTables')->willReturn([]);
        $this->assertSame(
            [],
            $this->commandAssembler->execute($this->connectionMock, $this->environmentMock, 'dump.gz', 'triggers.gz')
        );
    }

    public function testReturnsEmptyArrayIfAllTablesAreIgnored(): void
    {
        $this->tablesProviderMock->expects($this->any())->method('getAllTables')->willReturn(['a', 'b']);
        $this->tablesProviderMock->expects($this->any())->method('getIgnoredTables')->willReturn(['a', 'b']);
        $this->assertSame(
            [],
            $this->commandAssembler->execute($this->connectionMock, $this->environmentMock, 'dump.gz', 'triggers.gz')
        );
    }

    public function testReturnsCommandsForNormalTables(): void
    {
        $this->tablesProviderMock->expects($this->any())->method('getAllTables')->willReturn(['a', 'b']);
        $this->tablesProviderMock->expects($this->any())->method('getIgnoredTables')->willReturn([]);
        $this->assertSame(
            [
                "echo '/*!40014 SET @ORG_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;' | gzip >> dump.gz",
                "mysqldump --user=\"user\" --password=\"password\" --single-transaction --no-tablespaces --no-data --skip-triggers --host=host db | sed -E 's/DEFINER[ ]*=[ ]*`[^`]+`@`[^`]+`/DEFINER=CURRENT_USER/g' | gzip >> dump.gz",
                'mysqldump --user="user" --password="password" --single-transaction --no-tablespaces --no-create-info --skip-triggers --host=host db a | gzip >> dump.gz',
                'mysqldump --user="user" --password="password" --single-transaction --no-tablespaces --no-create-info --skip-triggers --host=host db b | gzip >> dump.gz',
                "echo '/*!40014 SET FOREIGN_KEY_CHECKS=@ORG_FOREIGN_KEY_CHECKS */;' | gzip >> dump.gz",
                "mysqldump --user=\"user\" --password=\"password\" --single-transaction --no-tablespaces --no-data --no-create-info --triggers --host=host db | sed -E 's/DEFINER[ ]*=[ ]*`[^`]+`@`[^`]+`/DEFINER=CURRENT_USER/g' | gzip >> triggers.gz"
            ],
            $this->commandAssembler->execute($this->connectionMock, $this->environmentMock, 'dump.gz', 'triggers.gz')
        );
    }

    public function testReturnsCommandsForEmptyTables(): void
    {
        $this->tablesProviderMock->expects($this->any())->method('getAllTables')->willReturn(['a', 'b']);
        $this->tablesProviderMock->expects($this->any())->method('getIgnoredTables')->willReturn([]);
        $this->tablesProviderMock->expects($this->any())->method('getEmptyTables')->willReturn(['a', 'b']);
        $this->assertSame(
            [
                "echo '/*!40014 SET @ORG_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;' | gzip >> dump.gz",
                "mysqldump --user=\"user\" --password=\"password\" --single-transaction --no-tablespaces --no-data --skip-triggers --host=host db | sed -E 's/DEFINER[ ]*=[ ]*`[^`]+`@`[^`]+`/DEFINER=CURRENT_USER/g' | gzip >> dump.gz",
                "echo '/*!40014 SET FOREIGN_KEY_CHECKS=@ORG_FOREIGN_KEY_CHECKS */;' | gzip >> dump.gz",
                "mysqldump --user=\"user\" --password=\"password\" --single-transaction --no-tablespaces --no-data --no-create-info --triggers --host=host db | sed -E 's/DEFINER[ ]*=[ ]*`[^`]+`@`[^`]+`/DEFINER=CURRENT_USER/g' | gzip >> triggers.gz"
            ],
            $this->commandAssembler->execute($this->connectionMock, $this->environmentMock, 'dump.gz', 'triggers.gz')
        );
    }

    public function testReturnsCommandsForMixedTables(): void
    {
        $this->tablesProviderMock->expects($this->any())->method('getAllTables')
            ->willReturn(['a', 'b', 'c', 'd', 'e', 'f']);
        $this->tablesProviderMock->expects($this->any())->method('getIgnoredTables')->willReturn(['c', 'f']);
        $this->tablesProviderMock->expects($this->any())->method('getEmptyTables')->willReturn(['b', 'e']);
        $this->assertSame(
            [
                "echo '/*!40014 SET @ORG_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;' | gzip >> dump.gz",
                "mysqldump --user=\"user\" --password=\"password\" --single-transaction --no-tablespaces --no-data --skip-triggers --host=host db --ignore-table=db.c --ignore-table=db.f | sed -E 's/DEFINER[ ]*=[ ]*`[^`]+`@`[^`]+`/DEFINER=CURRENT_USER/g' | gzip >> dump.gz",
                'mysqldump --user="user" --password="password" --single-transaction --no-tablespaces --no-create-info --skip-triggers --host=host db a | gzip >> dump.gz',
                'mysqldump --user="user" --password="password" --single-transaction --no-tablespaces --no-create-info --skip-triggers --host=host db d | gzip >> dump.gz',
                "echo '/*!40014 SET FOREIGN_KEY_CHECKS=@ORG_FOREIGN_KEY_CHECKS */;' | gzip >> dump.gz",
                "mysqldump --user=\"user\" --password=\"password\" --single-transaction --no-tablespaces --no-data --no-create-info --triggers --host=host db --ignore-table=db.c --ignore-table=db.f | sed -E 's/DEFINER[ ]*=[ ]*`[^`]+`@`[^`]+`/DEFINER=CURRENT_USER/g' | gzip >> triggers.gz"
            ],
            $this->commandAssembler->execute($this->connectionMock, $this->environmentMock, 'dump.gz', 'triggers.gz')
        );
    }
}
