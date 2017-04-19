# Contributing

> First and foremost, thank you for stepping in and trying to be part of the effort.

### Goals

When expanding Kint, keep in mind its main priorities:

1. **Maximum PHP version range compatibility**: Kint supports and must continue to work down to at least PHP 5.1.2 and support all latest stable PHP features and additions as soon as they are released
2. **Ease of use**: developers must be able to install Kint effortlessly and start using it immediately with no additional required steps. JS/CSS resources and the single PHP file should be rebuilt with `composer build` and committed when changed.
3. **No feature creep**: requests for edge use cases, especially ones that can be worked around with some configuration, should *not* be catered to. The definition of edge case is the popularity of the feature request, if it's popular - it's a workflow, if not - it might be an edge case.
4. **Do not compromise information clarity**: displaying relevant information and not omitting meaningful data takes precedence over every principle in this list - as well as (and not limited to) performance and tidy Kint codebase.
5. **Code quality**: the nature of Kint implies that it will be continued to develop for many years - at least to adapt to new language features and quirks. Keep the codebase as maintainable as possible. Keep code style consistent by finishing every commit with `composer format`.
6. **Stable releases**: Try not to push backwards compatibility breaking or untested changes to the master branch. Because it is a development tool, people may leave their `composer require-dev` Kint configuration to allow auto-updates.

### Don't alter user input

For the parser to do proper recursion checking it has to take things by reference. Since it's taking things by reference there's a good chance your parser plugin can change your end-users data. **NEVER ALTER INPUT VARIABLES**

### Development instructions

```sh
composer install
composer format # Fix code style
composer build # Build JS/CSS resources, single PHP file
```

Note: Full format and build depends on npm and bundler being in your `$PATH`.
