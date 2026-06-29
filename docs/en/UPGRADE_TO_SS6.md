# Upgrade Guide: Moving to Silverstripe 6

This document outlines the necessary changes to upgrade your project to be compatible with `sunnysideup/payment-alipay` for Silverstripe 6.

## New Requirements

The core dependencies have been updated to support the new Silverstripe 6 ecosystem.

⚠️ **BREAKING CHANGE:** You must update your project's `composer.json` to require the following major new versions:

-   **`silverstripe/framework`**: Update your constraint to `^6.0`.
-   **`silverstripe/admin`**: Update your constraint to `^3.0`.

These updates will likely require significant changes throughout your own project code to ensure compatibility with the new framework and admin modules.
