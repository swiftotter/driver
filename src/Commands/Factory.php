<?php

declare(strict_types=1);

namespace Driver\Commands;

use DI\Container;
use Driver\System\Configuration;

class Factory
{
    private Configuration $configuration;
    private Container $container;
    /** @var array<string, string>|null */
    private ?array $substitutions = null;

    public function __construct(Configuration $configuration, Container $container)
    {
        $this->configuration = $configuration;
        $this->container = $container;
    }

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
    public function create(string $name, array $properties = []): CommandInterface
    {
        $className = $this->getClassName($name);
        return $this->container->make($className, ['properties' => $properties]);
    }

    private function getClassName(string $name): string
    {
        $class = $this->runSubstitutions((string)$this->configuration->getNode("commands/{$name}/class"));
        if (class_exists($class) && in_array(CommandInterface::class, class_implements($class))) {
            return $class;
        } else {
            var_dump($class);
            throw new \Exception(
                "{$name} doesn't exist or it doesn't implement the type " . CommandInterface::class . "."
            );
        }
    }

    private function runSubstitutions(string $name): string
    {
        $substitutions = $this->getSubstitutions();
        preg_match_all("/%(.+)%/U", $name, $matches);

        if (count($matches) > 1) {
            $replacements = array_reduce($matches[1], function ($carry, $name) use ($substitutions) {
                $carry['%' . $name . '%'] = $substitutions[$name];
                return $carry;
            }, []);

            return str_replace(array_keys($replacements), array_values($replacements), $name);
        } else {
            return $name;
        }
    }

    /**
     * @return string[]
     */
    private function getSubstitutions(): array
    {
        if (!$this->substitutions) {
            $databaseEngine = $this->configuration->getNode('connections/database');
            if (is_array($databaseEngine)) {
                $databaseEngine = 'mysql';
            }

            $substitutions = [
                'engine' => $this->configuration->getNode("engines/{$databaseEngine}/class-name")
            ];
            $this->substitutions = $substitutions;
        }

        return $this->substitutions;
    }
}
