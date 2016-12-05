<?php
/**
 * SwiftOtter_Base is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * SwiftOtter_Base is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with SwiftOtter_Base. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Joseph Maxwell
 * @copyright SwiftOtter Studios, 12/5/16
 * @package default
 **/

namespace Driver\Commands\Transformations\Magento1;

use Driver\Commands\CommandInterface;
use Driver\Engines\MySql\Sandbox\Connection;
use Driver\Engines\MySql\Sandbox\Utilities;
use Driver\Pipes\Transport\TransportInterface;
use Symfony\Component\Console\Command\Command;

class ClearQuotes extends Command implements CommandInterface
{
    private $connection;
    private $utilities;

    public function __construct(Connection $connection, Utilities $utilities)
    {
        $this->connection = $connection->getConnection();
        $this->utilities = $utilities;

        parent::__construct('mysql-transformations-clear-orders');
    }

    public function go(TransportInterface $transport)
    {
        $this->utilities->clearTable($this->utilities->tableName('report_compared_product_index'));
        $this->utilities->clearTable($this->utilities->tableName('report_event'));
        $this->utilities->clearTable($this->utilities->tableName('report_event_types'));
        $this->utilities->clearTable($this->utilities->tableName('mage_report_viewed_product_aggregated_daily'));
        $this->utilities->clearTable($this->utilities->tableName('mage_report_viewed_product_aggregated_monthly'));
        $this->utilities->clearTable($this->utilities->tableName('mage_report_viewed_product_aggregated_yearly'));
        $this->utilities->clearTable($this->utilities->tableName('mage_report_viewed_product_index'));
        $this->utilities->clearTable($this->utilities->tableName('mage_sales_bestsellers_aggregated_daily'));
        $this->utilities->clearTable($this->utilities->tableName('mage_sales_bestsellers_aggregated_monthly'));
        $this->utilities->clearTable($this->utilities->tableName('mage_sales_bestsellers_aggregated_yearly'));
    }
}