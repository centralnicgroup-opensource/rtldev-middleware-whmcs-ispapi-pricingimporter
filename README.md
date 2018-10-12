# WHMCS "ISPAPI" Pricing Importer Add-on #

[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](https://opensource.org/licenses/MIT)
[![PRs welcome](https://img.shields.io/badge/PRs-welcome-brightgreen.svg)](https://github.com/hexonet/php-sdk/blob/master/CONTRIBUTING.md)
[![Slack Widget](https://camo.githubusercontent.com/984828c0b020357921853f59eaaa65aaee755542/68747470733a2f2f73332e65752d63656e7472616c2d312e616d617a6f6e6177732e636f6d2f6e6774756e612f6a6f696e2d75732d6f6e2d736c61636b2e706e67)](https://hexonet-sdk.slack.com/messages/CD9AVRQ6N)

This Repository covers the WHMCS Pricing Importer Add-on of HEXONET. It provides the following features in WHMCS:

## Supported Features ##

* Load TLDs with prices through three possible ways
  * HEXONET costs
  * HEXONET Price Classes
  * Custom CSV file
* Update existing extensions
* Flexible performance of step-wise pricing imports
* Support for different currencies
* Bulk price update by using a factor or a fixed amount
* Edition of prices individually
* Supported fields:
  * TLD
  * Register/Transfer/Renew pricing
  * DNS Management
  * Email Forwarding
  * ID Protection
  * EPP Code
  * Currency

... and MORE!

## Resources ##

* [Usage Guide](https://github.com/hexonet/whmcs-ispapi-pricingimporter/blob/master/README.md#usage-guide)
* [Release Notes](https://github.com/hexonet/whmcs-ispapi-pricingimporter/releases)
* [Development Guide](https://github.com/hexonet/whmcs-ispapi-pricingimporter/wiki/Development-Guide)

NOTE: We introduced sematic-release starting with v2.4.0. This is why older Release Versions do not appear in the [current changelog](HISTORY.md). But these versions appear in the [release overview](https://github.com/hexonet/whmcs-ispapi-pricingimporter/releases) and in the [old changelog](HISTORY.old).

## Usage Guide ##

Download the ZIP archive including the latest release version [here](https://github.com/hexonet/whmcs-ispapi-pricingimporter/raw/master/whmcs-ispapi-pricingimporter-latest.zip).

Copy all files from the install/ subdirectory to your WHMCS installation root directory ($YOUR_WHMCS_ROOT), while keeping the folder structure intact.

E.g.

```text
install/modules/addons/ispapidpi/ispapidpi.php
=> $YOUR_WHMCS_ROOT/modules/addons/ispapidpi/ispapidpi.php
```

Login to the WHMCS Admin Area and navigate to `Setup > Addon Modules` to activate.

## Minimum Requirements ##

For the latest WHMCS minimum system requirements, please refer to
[https://docs.whmcs.com/System_Requirements](https://docs.whmcs.com/System_Requirements)

## Contributing ##

Please read [our development guide](https://github.com/hexonet/whmcs-ispapi-pricingimporter/wiki/Development-Guide) for details on our code of conduct, and the process for submitting pull requests to us.

## Authors ##

* **Anthony Schneider** - *development* - [AnthonySchn](https://github.com/anthonyschn)
* **Kai Schwarz** - *development* - [PapaKai](https://github.com/papakai)
* **Tulasi Seelamkurthi** - *development* - [Tulsi91](https://github.com/tulsi91)

See also the list of [contributors](https://github.com/hexonet/whmcs-ispapi-pricingimporter/graphs/contributors) who participated in this project.

## License ##

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

[HEXONET GmbH](https://hexonet.net)