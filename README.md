# Merlin

[![CircleCI](https://circleci.com/gh/salsadigitalauorg/merlin-framework.svg?style=svg&circle-token=420489c9f298ba80a101b85a11fca5c202dcc1e4)](https://circleci.com/gh/salsadigitalauorg/merlin-framework)
[![License](https://img.shields.io/:license-gnu-blue.svg)](https://opensource.org/licenses/GPL-3.0)

Welcome to Merlin a simple tool to aid content migration from an arbitrary source to a structured format ready for consumption by another system.

Detailed documentation can be found https://salsadigitalauorg.github.io/merlin-framework/.

## Requirements

- PHP > 7.2
- Composer (optional)

## Installing

The Merlin framework is expected to be used as a standalone executable, this can be localised to your project or installed globally and added to your path. To download, visit the release page and download the latest bundled .phar executable.

```
curl -s https://github.com/salsadigitalauorg/merlin-framework/releases \
| grep "merlin-framework" \
| cut -d : -f 2,3 \
| tr -d \" \
| wget -qi -
```

### Composer dependency

Merlin can be installed as a composer dependency as well, this changes how the application is excuted for your project.

**Add the repository**

```
"repositories": [
  {
    "type": "vcs",
    "url": "https://github.com/salsadigitalauorg/merlin-framework"
  }
]
```

**Add the dependency**

```
composer require salsadigitalauorg/merlin-framework
```

## Usage

There are two primary commands: `crawl` and `generate`.

  * `crawl` will run crawl a domain and find URLs on a domain for migration. [Read the crawler docs](https://salsadigitalauorg.github.io/merlin-framework/docs/crawler) and check the [example](https://github.com/salsadigitalauorg/merlin-framework/blob/master/examples/crawler.yml) for more information.
  * `generate` will generate structured output based on mapping configuration. [Read the migration docs](https://salsadigitalauorg.github.io/merlin-framework/docs/examples) and check the [example](https://github.com/salsadigitalauorg/merlin-framework/blob/master/examples/basic_page.yml) for more information.

To run the framework you need to specify a command (e.g crawl or generate), a configuration yaml file, and a path to the output, e.g:

```
merlin crawl -c <path/to/crawler-config.yml> -o <path/to/output>
merlin generate -c <path/to/migrate-config.yml> -o <path/to/output>
```

### Configuration files

The configuration file should be treated as a schema file, this contains the paths, domains and mapping information to transform a HTML representation of content into structured JSON.

Example configuration files can be found in the [examples](https://github.com/salsadigitalauorg/merlin-framework/tree/master/examples).

## Testing

The automated testing suite tests standard configuration values against representative HTML structure to make sure that the tool can correctly build the JSON structure.

**Running the tests**

```
./vendor/bin/phpunit
```

## Support

We encourage you to file issues with the github issue queue.

## License

[![License](https://img.shields.io/:license-gnu-blue.svg)](https://opensource.org/licenses/GPL-3.0)

- [GPL3.0](https://opensource.org/licenses/GPL-3.0)
