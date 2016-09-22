# WC Cyclone

This is a WordPress plugin that adds several WP-CLI commands for generating fake WooCommerce data. It's still in its early days and as such doesn't yet offer coupon generating, more complicated order generating and refund generating.

### Requires

* [WP-CLI](http://wp-cli.org/)
* [Composer](http://getcomposer.org/)

### Installing
Make sure you run `composer install` within the plugin's directly before actually using it.

You'll also need to run `wp cyclone seed` before you can generate products. More on that below.

### Commands

```
wp cyclone seed [--type=<type>]
```

Seeds the product data for WC Cyclone to use, which pretty much means downloading a lot of Unsplash images. You can optionally set the type to be `books` or `food` (books by default).

---

```
wp cyclone unsplash
```

Set the Unsplash APP Id for WC Cyclone to use. In case you don't want to use WC Cyclone's Unsplash application. You can create the app on [Unsplash's website](https://unsplash.com/developers).

---

```
wp cyclone products <amount> [--type=<type>]
```

`<amount>` is required and should be integer # of products to generate. Note that the example data included doesn't really have more than a couple hundred unique items, so if were to generate several hundred/thousand products, they'd likely have the same name/images (but different prices, SKUs, etc.).

You can also set the type here. By default it's `books` but can also be `food`.

---

```
wp cyclone customers <amount> [--from=<from>]
```

Generate customers. `<amount>` is an integer # of customers to generate.

The `from` option lets you define the # of days in the past customers should be generated from. It's a random date used, but this lets it be a random date `x` days ago. By default it's `180`.

---

```
wp cyclone orders <amount> [--from=<from>]
```

Generate orders. `<amount>` is an integer # of orders to generate.

The `from` option lets you define the # of days in the past orders should be generated from. It's a random date used, but this lets it be a random date `x` days ago. By default it's `180`.

### Pro-tip

Don't just sit there watching it generate data! You can have it run as a [background job](http://unix.stackexchange.com/questions/103731/run-a-command-without-making-me-wait). Simply append `&` and optionally prepend the command with `nohup` (to generate a log). For example:

```
wp cyclone orders 100000 &
```

or...

```
nohup wp cyclone orders 100000 &
```


### What's coming

Scheduled/cron-powered generating, so you can have data generated on a random, but consistent basis.

### Contributing

Contributions are definitely welcomed and encouraged. Please feel free to open a PR with any changes you think should be made.

### License
[MIT](https://opensource.org/licenses/MIT)

Copyright (c) 2016+ Bryce Adams / Metorik