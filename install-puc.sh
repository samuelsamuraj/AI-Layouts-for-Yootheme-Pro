#!/bin/bash

# Install Plugin Update Checker
echo "Installing Plugin Update Checker..."

# Create vendor directory
mkdir -p vendor

# Download PUC from GitHub
curl -L -o vendor/plugin-update-checker.zip https://github.com/YahnisElsts/plugin-update-checker/archive/refs/heads/master.zip

# Unzip and rename
cd vendor
unzip plugin-update-checker.zip
mv plugin-update-checker-master plugin-update-checker
rm plugin-update-checker.zip

echo "Plugin Update Checker installed successfully!"
echo "You can now run: composer install"
