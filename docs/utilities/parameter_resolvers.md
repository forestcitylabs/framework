# Parameter Resolvers

The primary purpose of parameter resolvers is to examine a function or class method using reflection and return an array of arguments (keyed by parameter name).

!!! note

    Several resolvers ship with the framework, however, making your own is trivial using the `ParameterResolverInterface`.

The goal of a parameter _resolver_ is to fill in missing parameters, not convert them to another representation, to do that please take a look at [parameter converters](parameter_converters.md).
