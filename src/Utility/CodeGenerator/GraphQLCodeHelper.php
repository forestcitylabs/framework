<?php

declare(strict_types=1);

namespace ForestCityLabs\Framework\Utility\CodeGenerator;

use ForestCityLabs\Framework\GraphQL\Attribute as GraphQL;
use GraphQL\Type\Definition\Argument;
use GraphQL\Type\Definition\EnumType as DefinitionEnumType;
use GraphQL\Type\Definition\EnumValueDefinition;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\InputObjectField;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NamedType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\WrappingType;
use Nette\PhpGenerator\ClassLike;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\EnumCase;
use Nette\PhpGenerator\EnumType;
use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\Parameter;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\Property;

class GraphQLCodeHelper
{
    public static function updateType(
        ClassLike $class,
        NamedType $type
    ): ClassLike {
        // Determine type.
        switch ($type::class) {
            case ObjectType::class:
                $attribute = GraphQL\ObjectType::class;
                break;
            case InterfaceType::class:
                $attribute = GraphQL\InterfaceType::class;
                break;
            case DefinitionEnumType::class;
                $attribute = GraphQL\EnumType::class;
                break;
            case InputObjectType::class:
                $attribute = GraphQL\InputType::class;
                break;
        }

        // Extract existing attribute (if it exists).
        $attributes = $class->getAttributes();
        foreach ($attributes as $k => $a) {
            if ($a->getName() === $attribute) {
                unset($attributes[$k]);
            }
        }
        $class->setAttributes($attributes);

        // Build new attribute for class.
        $args = [];
        if ($type->name !== $class->getName()) {
            $args['name'] = $type->name;
        }
        if (!empty($type->description)) {
            $args['description'] = $type->description;
        }

        // Add attribute back to class.
        $class->addAttribute($attribute, $args);

        // Return the class.
        return $class;
    }

    public static function buildObjectType(
        PhpNamespace $namespace,
        ClassType $class,
        ObjectType $type
    ): ClassType {
        // Add GraphQL attribute use.
        $namespace->addUse(GraphQL::class, 'GraphQL');

        // Build attribute args.
        $args = [];
        if ($class->getName() !== $type->name) {
            $args['name'] = $type->name;
        }
        if (!empty($type->description)) {
            $args['description'] = $type->description;
        }

        // Add attribute to class.
        $class->addAttribute(GraphQL\ObjectType::class, $args);

        // Return the class.
        return $class;
    }

    public static function buildInterfaceType(
        PhpNamespace $namespace,
        ClassType $class,
        InterfaceType $type
    ): ClassType {
        // Add GraphQL attribute use.
        $namespace->addUse(GraphQL::class, 'GraphQL');

        // Build attribute args.
        $args = [];
        if ($class->getName() !== $type->name) {
            $args['name'] = $type->name;
        }
        if (!empty($type->description)) {
            $args['description'] = $type->description;
        }

        // Add attribute to class.
        $class->addAttribute(GraphQL\InterfaceType::class, $args);

        // Return the class.
        return $class;
    }

    public static function buildInputType(
        PhpNamespace $namespace,
        ClassType $class,
        InputObjectType $type
    ): ClassType {
        // Add GraphQL attribute use.
        $namespace->addUse(GraphQL::class, 'GraphQL');

        // Build attribute args.
        $args = [];
        if ($class->getName() !== $type->name) {
            $args['name'] = $type->name;
        }
        if (!empty($type->description)) {
            $args['description'] = $type->description;
        }

        // Add attribute to class.
        $class->addAttribute(GraphQL\InputType::class, $args);

        // Return the class.
        return $class;
    }

    public static function buildEnumType(
        PhpNamespace $namespace,
        EnumType $enum,
        DefinitionEnumType $type
    ): EnumType {
        // Add GraphQL attribute use.
        $namespace->addUse(GraphQL::class, 'GraphQL');

        // Build attribute args.
        $args = [];
        if ($enum->getName() !== $type->name) {
            $args['name'] = $type->name;
        }
        if (!empty($type->description)) {
            $args['description'] = $type->description;
        }

        // Add attribute to class.
        $enum->addAttribute(GraphQL\EnumType::class, $args);

        // Return the class.
        return $enum;
    }

    public static function addPropertyField(
        PhpNamespace $namespace,
        ClassType $class,
        FieldDefinition $field,
        string $property_name,
        string $property_type
    ): Property {
        // Add GraphQL attribute use.
        $namespace->addUse(GraphQL::class, 'GraphQL');

        // Create the property.
        $property = $class->addProperty($property_name);
        $property->setVisibility('protected');

        // Return the property.
        return self::buildPropertyField($namespace, $property, $field, $property_type);
    }

    public static function updatePropertyField(
        PhpNamespace $namespace,
        ClassType $class,
        FieldDefinition $field,
        string $property_type
    ): Property {
        // Extract the property.
        $property = self::extractFieldProperty($class, $field);

        // Remove the existing attribute.
        $attributes = $property->getAttributes();
        foreach ($attributes as $k => $attr) {
            $name = $attr->getArguments()['name'] ?? $property->getName();
            if ($attr->getName() === GraphQL\Field::class && $name === $field->name) {
                unset($attributes[$k]);
            }
        }
        $property->setAttributes($attributes);

        // Rebuild the property.
        return self::buildPropertyField($namespace, $property, $field, $property_type);
    }

    public static function buildPropertyField(
        PhpNamespace $namespace,
        Property $property,
        FieldDefinition $field,
        string $property_type
    ): Property {
        // Determine the type.
        list($type, $list_of, $not_null) = self::unwrapType($field->getType());

        // Set nullable and type.
        $property->setNullable(!$not_null);
        $property->setType($list_of ? 'array' : $property_type);

        // If the type is not scalar add a use.
        if (!$list_of && !self::isScalar($property_type)) {
            $namespace->addUse($property_type);
        }

        // Build attribute args.
        $args = [];
        if ($property->getName() !== $field->name) {
            $args['name'] = $field->name;
        }
        if (!empty($field->description)) {
            $args['description'] = $field->description;
        }
        if (!empty($field->deprecationReason)) {
            $args['deprecation_reason'] = $field->deprecationReason;
        }
        if ($list_of) {
            $args['type'] = $type->name;
        }

        // Add attribute to property.
        $property->addAttribute(GraphQL\Field::class, $args);

        // Return the property.
        return $property;
    }

    public static function addMethodField(
        PhpNamespace $namespace,
        ClassType $class,
        FieldDefinition $field,
        string $method_name,
        string $method_type
    ): Method {
        // Add GraphQL attribute use.
        $namespace->addUse(GraphQL::class, 'GraphQL');

        // Create the method.
        $method = $class->addMethod($method_name);

        // Build and return the method field.
        return self::buildMethodField($namespace, $method, $field, $method_type);
    }

    public static function updateMethodField(
        PhpNamespace $namespace,
        ClassType $class,
        FieldDefinition $field,
        string $method_type
    ): Method {
        // Extract the method.
        $method = self::extractFieldMethod($class, $field);

        // Remove the existing attribute.
        $attributes = $method->getAttributes();
        foreach ($attributes as $k => $attr) {
            $name = $attr->getArguments()['name'] ?? $method->getName();
            if ($attr->getName() === GraphQL\Field::class && $name === $field->name) {
                unset($attributes[$k]);
            }
        }
        $method->setAttributes($attributes);

        // Rebuild the method.
        return self::buildMethodField($namespace, $method, $field, $method_type);
    }

    public static function buildMethodField(
        PhpNamespace $namespace,
        Method $method,
        FieldDefinition $field,
        string $method_type
    ): Method {
        // Determine the type.
        list($type, $list_of, $not_null) = self::unwrapType($field->getType());

        // Set nullable and type.
        $method->setReturnNullable(!$not_null);
        $method->setReturnType($list_of ? 'array' : $method_type);

        // If the type is not scalar add a use.
        if (!$list_of && !self::isScalar($method_type)) {
            $namespace->addUse($method_type);
        }

        // Build atrribute args.
        $args = [];
        if ($method->getName() !== $field->name) {
            $args['name'] = $field->name;
        }
        if (!empty($field->description)) {
            $args['description'] = $field->description;
        }
        if (!empty($field->deprecationReason)) {
            $args['deprecation_reason'] = $field->deprecationReason;
        }
        if ($list_of) {
            $args['type'] = $type->name;
        }

        // Add attribute to method.
        $method->addAttribute(GraphQL\Field::class, $args);

        // Return the method.
        return $method;
    }

    public static function addParameterArgument(
        PhpNamespace $namespace,
        Method $method,
        Argument $arg,
        string $parameter_name,
        string $parameter_type
    ): Parameter {
        // Add GraphQL attribute use.
        $namespace->addUse(GraphQL::class, 'GraphQL');

        // Create the parameter.
        $parameter = $method->addParameter($parameter_name);

        // Build and return the parameter.
        return self::buildParameterArgument($namespace, $parameter, $arg, $parameter_type);
    }

    public static function updateParameterArgument(
        PhpNamespace $namespace,
        Method $method,
        Argument $arg,
        string $parameter_type
    ): Parameter {
        // Find the correct parameter.
        $parameter = self::extractArgumentParameter($method, $arg);

        // Remove the existing attribute.
        $attributes = $parameter->getAttributes();
        foreach ($attributes as $k => $attr) {
            $name = $attr->getArguments()['name'] ?? $parameter->getName();
            if ($attr->getName() === GraphQL\Argument::class && $name === $arg->name) {
                unset($attributes[$k]);
            }
        }
        $parameter->setAttributes($attributes);

        // Build and return new parameter.
        return self::buildParameterArgument($namespace, $parameter, $arg, $parameter_type);
    }

    public static function buildParameterArgument(
        PhpNamespace $namespace,
        Parameter $parameter,
        Argument $arg,
        string $parameter_type
    ): Parameter {
        // Determine the type.
        list($type, $list_of, $not_null) = self::unwrapType($arg->getType());

        // Set nullable and type.
        $parameter->setNullable(!$not_null);
        $parameter->setType($list_of ? 'array' : $parameter_type);

        // If the type is not scalar add a use.
        if (!$list_of && !self::isScalar($parameter_type)) {
            $namespace->addUse($parameter_type);
        }

        // Build atrribute args.
        $args = [];
        if ($parameter->getName() !== $arg->name) {
            $args['name'] = $arg->name;
        }
        if (!empty($arg->description)) {
            $args['description'] = $arg->description;
        }
        if (!empty($arg->deprecationReason)) {
            $args['deprecation_reason'] = $arg->deprecationReason;
        }
        if ($list_of) {
            $args['type'] = $type->name;
        }

        // Add attribute to parameter.
        $parameter->addAttribute(GraphQL\Argument::class, $args);

        // Return the parameter.
        return $parameter;
    }

    public static function addPropertyArgument(
        PhpNamespace $namespace,
        ClassType $class,
        InputObjectField $field,
        string $property_name,
        string $property_type
    ): Property {
        // Add GraphQL attribute use.
        $namespace->addUse(GraphQL::class, 'GraphQL');

        // Create the property.
        $property = $class->addProperty($property_name);
        $property->setVisibility('protected');

        return self::buildPropertyArgument($namespace, $property, $field, $property_type);
    }

    public static function updatePropertyArgument(
        PhpNamespace $namespace,
        ClassType $class,
        InputObjectField $field,
        string $property_type
    ): Property {
        // Extract the property.
        $property = self::extractArgumentProperty($class, $field);

        // Remove the existing attribute.
        $attributes = $property->getAttributes();
        foreach ($attributes as $k => $attr) {
            $name = $attr->getArguments()['name'] ?? $property->getName();
            if ($attr->getName() === GraphQL\Argument::class && $name === $field->name) {
                unset($attributes[$k]);
            }
        }
        $property->setAttributes($attributes);

        // Build and return the new property.
        return self::buildPropertyArgument($namespace, $property, $field, $property_type);
    }

    public static function buildPropertyArgument(
        PhpNamespace $namespace,
        Property $property,
        InputObjectField $field,
        string $property_type
    ): Property {
        // Determine the type.
        list($type, $list_of, $not_null) = self::unwrapType($field->getType());

        // Set nullable and type.
        $property->setNullable(!$not_null);
        $property->setType($list_of ? 'array' : $property_type);

        // If the type is not scalar add a use.
        if (!$list_of && !self::isScalar($property_type)) {
            $namespace->addUse($property_type);
        }

        // Build attribute args.
        $args = [];
        if ($property->getName() !== $field->name) {
            $args['name'] = $field->name;
        }
        if (!empty($field->description)) {
            $args['description'] = $field->description;
        }
        if (!empty($field->deprecationReason)) {
            $args['deprecation_reason'] = $field->deprecationReason;
        }
        if ($list_of) {
            $args['type'] = $type->name;
        }

        // Add attribute to property.
        $property->addAttribute(GraphQL\Argument::class, $args);

        // Return the property.
        return $property;
    }

    public static function addCaseValue(
        PhpNamespace $namespace,
        EnumType $enum,
        EnumValueDefinition $value,
        string $case_name
    ): EnumCase {
        // Add GraphQL attribute use.
        $namespace->addUse(GraphQL::class, 'GraphQL');

        // Create the case.
        $case = $enum->addCase($case_name);

        // Build and return the case.
        return self::buildCaseValue($case, $value);
    }

    public static function updateCaseValue(
        EnumType $enum,
        EnumValueDefinition $value
    ): EnumCase {
        $case = self::extractValueCase($enum, $value);

        // Remove the attribute.
        $attributes = $case->getAttributes();
        foreach ($attributes as $k => $attr) {
            $name = $attr->getArguments()['name'] ?? $case->getName();
            if ($attr->getName() === GraphQL\Value::class && $name === $value->name) {
                unset($attributes[$k]);
            }
        }
        $case->setAttributes($attributes);

        // Build and return the case.
        return self::buildCaseValue($case, $value);
    }

    public static function buildCaseValue(
        EnumCase $case,
        EnumValueDefinition $value
    ): EnumCase {
        // Build attribute args.
        $args = [];
        if ($case->getName() !== $value->name) {
            $args['name'] = $value->name;
        }
        if (!empty($value->description)) {
            $args['description'] = $value->description;
        }
        if (!empty($value->deprecationReason)) {
            $args['deprecation_reason'] = $value->deprecationReason;
        }

        // If the value does not match the name set it.
        if (!empty($value->value) && $value->value !== $value->name) {
            $case->setValue($value->value);
            $args['value'] = $value->value;
        }

        // Add attribute to case.
        $case->addAttribute(GraphQL\Value::class, $args);

        // Return the case.
        return $case;
    }

    public static function buildMethodFieldAttributeArgs(Method $method, FieldDefinition $field): array
    {
        list($type, $list_of, ) = self::unwrapType($field->getType());
        $args = [];
        if ($method->getName() !== $field->name) {
            $args['name'] = $field->name;
        }
        if (!empty($field->description)) {
            $args['description'] = $field->description;
        }
        if (!empty($field->deprecationReason)) {
            $args['deprecation_reason'] = $field->deprecationReason;
        }
        if ($list_of) {
            $args['type'] = $type->name;
        }
    }

    public static function unwrapType(Type $type): array
    {
        // Is the property not null?
        $not_null = $type instanceof NonNull;
        $list_of = false;

        // Unwrap the type.
        while ($type instanceof WrappingType) {
            if ($type instanceof ListOfType) {
                $list_of = true;
            }
            $type = $type->getWrappedType();
        }

        return [$type, $list_of, $not_null];
    }

    public static function extractFieldMethod(ClassType $class, FieldDefinition $field): ?Method
    {
        foreach ($class->getMethods() as $method) {
            foreach ($method->getAttributes() as $attribute) {
                // Ensure this is the correct attribute.
                if ($attribute->getName() === GraphQL\Field::class) {
                    $name = $attribute->getArguments()['name'] ?? $method->getName();
                    if ($name === $field->name) {
                        return $method;
                    }
                }
            }
        }

        return null;
    }

    public static function extractFieldProperty(ClassType $class, FieldDefinition $field): ?Property
    {
        foreach ($class->getProperties() as $property) {
            foreach ($property->getAttributes() as $attribute) {
                // Ensure this is the correct attribute.
                if ($attribute->getName() === GraphQL\Field::class) {
                    $name = $attribute->getArguments()['name'] ?? $property->getName();
                    if ($name === $field->name) {
                        return $property;
                    }
                }
            }
        }

        return null;
    }

    public static function extractArgumentParameter(Method $method, Argument $arg): ?Parameter
    {
        foreach ($method->getParameters() as $parameter) {
            foreach ($parameter->getAttributes() as $attr) {
                // Ensure this is the correct attribute.
                $name = $attr->getArguments()['name'] ?? $parameter->getName();
                if ($attr->getName() === GraphQL\Argument::class && $name === $arg->name) {
                    return $parameter;
                }
            }
        }

        return null;
    }

    public static function extractArgumentProperty(ClassType $class, InputObjectField $field): ?Property
    {
        foreach ($class->getProperties() as $property) {
            foreach ($property->getAttributes() as $attr) {
                $name = $attr->getArguments()['name'] ?? $property->getName();
                if ($attr->getName() === GraphQL\Argument::class && $name === $field->name) {
                    return $property;
                }
            }
        }
        return null;
    }

    public static function extractValueCase(EnumType $enum, EnumValueDefinition $value): ?EnumCase
    {
        foreach ($enum->getCases() as $case) {
            foreach ($case->getAttributes() as $attr) {
                $name = $attr->getArguments()['name'] ?? $case->getName();
                if ($attr->getName() === GraphQL\Value::class && $name === $value->name) {
                    return $case;
                }
            }
        }
        return null;
    }

    public static function isScalar(string $type): bool
    {
        return (bool) in_array($type, ['string', 'int', 'float', 'bool', 'array']);
    }
}
