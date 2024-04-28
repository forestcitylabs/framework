<?php

namespace ForestCityLabs\Framework\Utility;

use Doctrine\Common\Collections\Collection;
use Doctrine\Inflector\InflectorFactory;
use Doctrine\Inflector\Language;
use Doctrine\ORM\Mapping as ORM;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Literal;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\Property;
use Ramsey\Uuid\Doctrine\UuidOrderedTimeGenerator;
use Ramsey\Uuid\Uuid;

class CodeGenerator
{
    public static function addIdProperty(ClassType $class, PhpNamespace $namespace): void
    {
        $namespace->addUse(Uuid::class);
        $namespace->addUse(UuidOrderedTimeGenerator::class);
        $class->addProperty('id')
            ->setPrivate()
            ->setType(Uuid::class)
            ->addAttribute(ORM\Id::class)
            ->addAttribute(
                ORM\GeneratedValue::class,
                ['strategy' => 'CUSTOM']
            )
            ->addAttribute(
                ORM\CustomIdGenerator::class,
                ['class' => new Literal('UuidOrderedTimeGenerator::class')]
            )
            ->addAttribute(ORM\Column::class, [
                'type' => 'uuid_binary_ordered_time',
                'unique' => true,
            ]);
        $class->addMethod('getId')
            ->setReturnType(Uuid::class)
            ->addBody('return $this->id;');
    }

    /**
     * Add a getter method for a given property to a given class.
     *
     * @param ClassType $class    The class to add a getter method to.
     * @param Property  $property The property to get.
     */
    public static function addGetter(ClassType $class, Property $property): void
    {
        $class->addMethod('get' . self::camelCase($property->getName()))
            ->setReturnType(($property->isNullable() ? '?' : '') . $property->getType())
            ->addBody('return $this->' . $property->getName() . ';');
    }

    /**
     * Add a setter method for a given property to a given class.
     *
     * @param ClassType $class    The class to add a setter method to.
     * @param Property  $property The property to set.
     */
    public static function addSetter(ClassType $class, Property $property): void
    {
        $class->addMethod('set' . self::camelCase($property->getName()))
            ->setReturnType('self')
            ->addBody('$this->' . $property->getName() . ' = $' . $property->getName() . ';')
            ->addBody('return $this;')
            ->addParameter($property->getName())
            ->setType(($property->isNullable() ? '?' : '') . $property->getType());
    }

    /**
     * Add an adder method for a given property to a given class.
     *
     * @param ClassType $class    The class to add an adder method to.
     * @param Property  $property The property to add to.
     */
    public static function addAdder(ClassType $class, Property $property, ?string $type = null): void
    {
        // Singularize the sub name.
        $sub_name = InflectorFactory::createForLanguage(Language::ENGLISH)->build()->singularize($property->getName());

        // Create adder method.
        $method = $class->addMethod('add' . self::camelCase($sub_name))
            ->setReturnType('self');

        // If this is a collection use api, otherwise treat as array.
        if ($property->getType() === Collection::class) {
            $method->addBody('$this->' . $property->getName() . '->add($' . $sub_name . ');');
        } else {
            $method->addBody('$this->' . $property->getName() . '[] = $' . $sub_name . ';');
        }

        // Add the return and parameter to the method.
        $method->addBody('return $this;')
            ->addParameter($sub_name)
            ->setType($type);
    }

    /**
     * Add a remover method for a given property to a given class.
     *
     * @param ClassType $class    The class to add a remover method to.
     * @param Property  $property The property to remove from.
     */
    public static function addRemover(ClassType $class, Property $property, ?string $type = null): void
    {
        // Singularize the sub name.
        $sub_name = InflectorFactory::createForLanguage(Language::ENGLISH)->build()->singularize($property->getName());

        // Create remover method.
        $method = $class->addMethod('remove' . self::camelCase($sub_name))
            ->setReturnType('self');

        // If this is a collection use api, otherwise treat as array.
        if ($property->getType() === Collection::class) {
            $method->addBody('$this->' . $property->getName() . '->removeElement($' . $sub_name . ');');
        } else {
            $method->addBody('unset($this->' . $property->getName() . '[array_search($' . $sub_name . ', $this->' . $property->getName() . ')]);');
        }

        // Add the return and parameter to the method.
        $method->addBody('return $this;')
            ->addParameter($sub_name)
            ->setType($type);
    }

    /**
     * Add a hasser method for a given property to a given class.
     *
     * @param ClassType $class    The class to add a hasser method to.
     * @param Property  $property The property to has from.
     */
    public static function addHasser(ClassType $class, Property $property, ?string $type = null): void
    {
        // Singularize the sub name.
        $sub_name = InflectorFactory::createForLanguage(Language::ENGLISH)->build()->singularize($property->getName());

        // Create hasser method.
        $method = $class->addMethod('has' . self::camelCase($sub_name))
            ->setReturnType('bool');

        // If this is a collection use api, otherwise treat as array.
        if ($property->getType() === Collection::class) {
            $method->addBody('return $this->' . $property->getName() . '->contains($' . $sub_name . ');');
        } else {
            $method->addBody('return in_array($' . $sub_name . ', $this->' . $property->getName() . ');');
        }

        // Add the parameter to the method.
        $method->addParameter($sub_name)
            ->setType($type);
    }

    public static function camelCase(string $string): string
    {
        $parts = explode('_', $string);
        array_walk($parts, function (&$part) {
            $part = ucfirst($part);
        });
        return implode($parts);
    }
}
