# PseudolocalizationBundle

This bundle integrates the [Pseudolocalization library](https://github.com/yoannrenard/pseudolocalization)
into Symfony so that you can generate pseudolocalized translations into your project.

## Requirements

* PHP 5.6 or higher;
* Symfony 2.8 or higher;

## Setup

### Installation

Using this package is similar to all Symfony bundles. The following steps must be performed :

1. Download the Bundle

Since this bundle is still under development and not released in Packagist yet, you have to specify the VCS repository into your composer.json :  

```json
"repositories": [
    {
        "type": "vcs",
        "url": "https://github.com/yoannrenard/pseudolocalization-bundle.git"
    }
]
```

And then run :

```bash
$ composer require yoannrenard/pimp-my-query-bundle:dev-master
```

2. Enable the Bundle (Already done using Symfony Flex)

```php
<?php

return [
    // ...
    YoannRenard\PseudolocalizationBundle\PseudolocalizationBundle::class => ['all' => true],
    // ...
];
```
