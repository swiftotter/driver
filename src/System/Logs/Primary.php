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
 * @copyright SwiftOtter Studios, 11/25/16
 * @package default
 **/

namespace Driver\System\Logs;

use Driver\System\Logs\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class Primary implements LoggerInterface
{
    /**
     * @var InputInterface $input
     */
    private $input;
    /**
     * @var OutputInterface $output
     */
    private $output;
    private $styler;

    private function getStyler()
    {
        if (!$this->styler && $this->input && $this->output) {
            $this->styler = new SymfonyStyle($this->input, $this->output);
        }

        return $this->styler;
    }

    public function setParams(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
    }

    public function emergency($message, array $context = array())
    {
        $this->log(10, $message);
    }

    public function alert($message, array $context = array())
    {
        $this->log(10, $message);
    }

    public function critical($message, array $context = array())
    {
        $this->log(10, $message);
    }

    public function error($message, array $context = array())
    {
        $this->log(9, $message);
    }

    public function warning($message, array $context = array())
    {
        $this->log(8, $message);
    }

    public function notice($message, array $context = array())
    {
        $this->log(5, $message);
    }

    public function info($message, array $context = array())
    {
        $this->log(2, $message);
    }

    public function debug($message, array $context = array())
    {
        $this->log(1, $message);
    }

    public function log($level, $message, array $context = array())
    {
        $styler = $this->getStyler();

        $message = date('m/d/y H:i:s') . ' ' . $message;

        if (!$styler) {
            return;
        }

        switch($level) {
            case 1:
            case 2:
            case 3:
                $styler->comment($message);
                break;
            case 4:
            case 5:
            case 6:
                $styler->comment($message);
                break;
            case 7:
            case 8:
                $styler->caution($message);
                break;
            case 9:
            case 10:
            default:
                $styler->error($message);
                break;
        }
    }


}