Attribute Reference
===================

The following attributes can be used to map your types and controllers.

ObjectType
----------

**Targets**: Classes

**Parameters**:

* name (optional): The name of the object type in the schema, defaults to the class name.
* description (optional): The description of the object type in the schema, defaults to null.

InputType
---------

**Targets**: Classes

**Parameters**:

* name (optional): The name of the input type in the schema, defaults to the class name.
* description (optional): The description of the input type in the schema, defaults to null.

InterfaceType
-------------

**Targets**: Classes

**Parameters**:

* name (optional): The name of the interface type in the schema, defaults to the class name.
* description (optional): The description of the interface type in the schema, defaults to null.

EnumType
--------

**Targets**: Classes

**Parameters**:

* name (optional): The name of the enum type in the schema, defaults to the class name.
* description (optional): The description of the enum type in the schema, defaults to null.

Field
-----

**Targets**: Class methods and properties

**Parameters**:

* name (optional): The name of the field in the schema, defaults to the property or method name.
* description (optional): The description of the field in the schema, defaults to null.
* list (optional): Whether this will return a list or not, will default to true if the type of the property or method is iterable.
* type (optional): The type for this field, will attempt to detect a default from the return type, automapping custom object types if possible.
* not_null (optional): Whether this is optional or not, will use the return type to determine a value if not set explicitly.
* deprecation_reason (optional): The deprecation reason for this field.
