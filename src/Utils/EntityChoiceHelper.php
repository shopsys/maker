<?php

declare(strict_types=1);

namespace Shopsys\MakerBundle\Utils;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Component\Console\Question\Question;

class EntityChoiceHelper
{
    /**
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param array<string, string> $entityExtensionMap
     */
    public function __construct(
        protected readonly EntityManagerInterface $em,
        protected readonly array $entityExtensionMap,
    ) {
    }

    /**
     * @param \Symfony\Bundle\MakerBundle\ConsoleStyle $io
     * @param string $questionText
     * @return string
     */
    public function askForEntity(ConsoleStyle $io, string $questionText): string
    {
        $entityClass = null;

        while ($entityClass === null) {
            $question = new Question($questionText);
            $question->setValidator(Validator::notBlank(...));

            $question->setAutocompleterValues($this->getAllAvailableEntitiesChoices());

            $answeredEntityClass = $this->convertAutocompleteFormatToFqcn($io->askQuestion($question));

            if (class_exists($answeredEntityClass)) {
                $entityClass = $answeredEntityClass;
            } else {
                $io->error(sprintf('Unknown class "%s"', $answeredEntityClass));
            }
        }

        return $entityClass;
    }

    /**
     * @return string[]
     */
    protected function getAllAvailableEntitiesChoices(): array
    {
        $allEntityNames = $this->em->getConfiguration()->getMetadataDriverImpl()?->getAllClassNames() ?? [];
        $allEntityNames = array_combine($allEntityNames, $allEntityNames);

        $array = array_values(array_diff_key($allEntityNames, array_flip(array_keys($this->entityExtensionMap))));

        return array_map($this->convertFqcnToAutocompleteFormat(...), $array);
    }

    /**
     * Converts a fully qualified class name to a format suitable for autocompletion.
     * E.g. "Shopsys\FrameworkBundle\Model\Store\Store" -> "Store (Shopsys\FrameworkBundle\Model\Store\Store)"
     *
     * @param string $fqcn
     * @return string
     */
    protected function convertFqcnToAutocompleteFormat(string $fqcn): string
    {
        $className = Str::getShortClassName($fqcn);

        return sprintf('%s (%s)', $className, $fqcn);
    }

    /**
     * @param string $autocompleteFormat
     * @return string
     */
    protected function convertAutocompleteFormatToFqcn(string $autocompleteFormat): string
    {
        if (preg_match('/\((.*?)\)$/', $autocompleteFormat, $matches)) {
            return $matches[1];
        }

        return $autocompleteFormat;
    }
}
