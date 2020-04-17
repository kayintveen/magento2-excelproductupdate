# Microdesign Excel Product Update

This Magento 2 modules makes it able to export product data and import csv files to update products attributes.

## Installation

```
composer config repositories.microdesign-excelproductupdate vcs https://github.com/kayintveen/magento2-excelproductupdate.git
composer require microdesign/microdesign/excelproductupdate
bin/magento module:enable Microdesign_ExcelProductUpdate
bin/magento setup:upgrade
```

## ToDo / Wishlist 

- Make filters to filter what products to export
- Make compatible to add products with simples and configurables 

## Changelog

### Unreleased

### [1.0.0] - 2020-04-17
- Exports all products based on selected attributes
- Imports csv files and uses headers to determine used attributes
- Attributes validated for existence
- Auto check if attribute is select or text
- Compatible for both Simples and Configurable products
- Compatible for text fields being multi lingual "short_description|nl_nl"

## Development by Microdesign

We are a Dutch Magento® agency developing sites and modules for Magento 2 

[Visit Microdesign.nl](https://www.microdesign.nl/)
