# Parameter Converters

The primary responsibility of a parameter _converter_ is to examine a function (or class method) and a list of passed arguments, converting arguments from one representation to another.

A good example is dates, you might want to accept dates as part of a URL pattern and convert the to a real `\DateTime` object before passing them to your controller.

!!! note

    This framework ships with several converters, however, it is trivial to make your own using the `ParameterConverterInterface`.
