# Changelog

All notable changes to `dgii-rnc-validator` will be documented in this file.

## 1.1.7 - 2024-05-16

- Add validation for empty or non-numeric RNC input string. (Thanks to @elminson for the contribution) #Hotfix

## 1.1.6 - 2023-02-10

- Updating the Status enum by validating all possible Status types from the API. I matched these values by comparing them from the DGII data file shared with all the existing RNC.

## 1.1.5 - 2023-02-09

- Fixing the getNumbersFromString from the Utils helper function to correctly return all numbers from the given string.

## 1.1.4 - 2023-02-07

- Using Enum returns over direct values in the rncType function on the DgiiRncValidator library.
- Adding the toCode method to the Types enum.

## 1.1.3 - 2023-02-07

- Removing the unnecessary fromText method from the Types enum.
- Improving return text from the toString method from the Types enum.

## 1.1.2 - 2023-02-07

- Adding helper method on the Types enum.

## 1.1.1 - 2023-02-07

- Fixing namespaces.

## 1.1.0 - 2023-02-07

- New Feature: RNC Type.

## 1.0.0 - 2023-01-21

- Initial release.
