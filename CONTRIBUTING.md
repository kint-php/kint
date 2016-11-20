# Contributing

> First and foremost, thank you for stepping in and trying to be part of the effort.

### Goals

When expanding Kint, keep in mind its main priorities:

1. **Maximum PHP version range compatibility**: Kint supports and must continue to work down to at least PHP 5.1 and support all latest stable PHP features and additions as soon as they are released
2. **Ease of use**: developers must be able to install Kint effortlessly and start using it immediately with no additional required steps. It should also provide flexible and simple to use configuration when the user feels confident enough. Through configuration Kint should be able to adapt to popular workflow scenarios.
3. **No feature creep**: requests for edge use cases, especially ones that can be worked around with some configuration, should *not* be catered to. The definition of edge case is the popularity of the feature request, if it's popular - it's a workflow, if not - it might be an edge case.
4. **Do not compromise information clarity**: displaying relevant information and not omitting meaningful data takes precedence over every principle in this list - as well as (and not limited to) performance and tidy Kint codebase.
5. **Code quality**: the nature of Kint implies that it will be continued to develop for many years - at least to adapt to new language features and quirks. Keep the codebase as maintainable as possible.
5. **Stable releases**: never publish versions that are not thoroughly tested to the master branch. Due to the nature of this project, any kind of meaningful test coverage is mostly impossible *and* because it is a development tool, people may leave their `composer require-dev` Kint configuration to allow auto-updates.

> To be continued: `Development instructions`, `Asset building`
