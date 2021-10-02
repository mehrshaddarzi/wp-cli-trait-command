# WP-CLI Trait Package Command

Generate plugin or php model files e.g. post-type or taxonomy for [WP-Trait](https://github.com/mehrshaddarzi/wp-trait) Package in Develop WordPress Plugin.

## Installation

You can install this package with:

```console
wp package install mehrshaddarzi/wp-cli-trait-command
```

> Installing this package requires WP-CLI v2 or greater. Update to the latest stable release with `wp cli update`.


## Structure

```
NAME

  wp trait

DESCRIPTION

  WP-CLI Trait Package Command.

SYNOPSIS

  wp trait <command>

SUBCOMMANDS

  start       Generate New Plugin With Wp-trait Structure
  make        Make PHP Model file
```

### Generate Plugin

Use This Command:

```
wp trait start
```

And fill Your Plugin information e.g. slug and namespace:

```
1/12 [--slug=<slug>]: wp-plugin
2/12 [--namespace=<namespace>]: WP_Plugin
3/12 [--plugin_name=<title>]: plugin-name
4/12 [--plugin_description=<description>]: q
5/12 [--plugin_author=<author>]: Mehrshad Darzi
6/12 [--plugin_author_uri=<url>]: https://github.com
7/12 [--plugin_uri=<url>]: http://github.com
8/12 [--skip-tests] (Y/n): n
9/12 [--ci=<provider>]: travis
10/12 [--activate] (Y/n): y
11/12 [--activate-network] (Y/n): n
12/12 [--force] (Y/n): y
```

### Make PHP Model

Create php model:

```
wp trait make model <class>
```

> Notice: To execute the command to create a model or post-type, you must change the directory of your terminal address from the WordPress root to the plugin folder. for example first run `cd wp-content/plugins/my-plugin` command and then run `wp trait make`

#### Create Model With Class Name:

```
wp trait make model Option
```

#### Create Model With Nested Namespace:

```
wp trait make User/Register
```

#### Create Model With Custom global Variable in Plugin:

```
wp trait make model User/Signup --var=register
```

### Make WordPress Post Type

Make a new post-type in WordPress:

```
wp trait make post-type <class> <slug> <singular-name>
```

#### Create A new Post Type With `City` slug:

```
wp trait make post-type City
```

After created You can change `register_post_type` argument in main plugin files.

#### Create a new Post Type With Nested NameSpace:

```
wp trait make post-type Post/Orders order
```

### Make WordPress Taxonomy

Make a new taxonomy in WordPress:

```
wp trait make taxonomy <class> <slug> <singular-name>
```

#### Create A new Taxonomy With `Country` slug:

```
wp trait make taxonomy Country
```

After create You can change register_taxonomy argument in main plugin files.

#### Create a new Taxonomy With Nested NameSpace and Custom name:

```
wp trait make taxonomy Media/Category media_cat Category
```

## Author

- [Mehrshad Darzi](https://www.linkedin.com/in/mehrshaddarzi/) | PHP Full Stack and WordPress Expert

## Contributing

We appreciate you taking the initiative to contribute to this project.

Contributing isn’t limited to just code. We encourage you to contribute in the way that best fits your abilities, by writing tutorials, giving a demo at your local meetup, helping other users with their support questions, or revising our documentation.

### Reporting a bug

Think you’ve found a bug? We’d love for you to help us get it fixed.
Before you create a new issue, you should [search existing issues](https://github.com/mehrshaddarzi/wp-cli-trait-command/issues) to see if there’s an existing resolution to it, or if it’s already been fixed in a newer version.

### Creating a pull request

Want to contribute a new feature? Please first [open a new issue](https://github.com/mehrshaddarzi/wp-cli-trait-command/issues/new) to discuss whether the feature is a good fit for the project.

Once you've decided to commit the time to seeing your pull request through, please follow our guidelines for creating a pull request to make sure it's a pleasant experience:

1. Create a feature branch for each contribution.
2. Submit your pull request early for feedback.
3. Follow [PSR-2 Coding Standards](http://www.php-fig.org/psr/psr-2/).
