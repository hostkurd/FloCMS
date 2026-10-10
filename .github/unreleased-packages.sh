#!/usr/bin/env bash
# CI only: until a pinned framework release is on Packagist, require it from
# its release branch. Once it is tagged, this does nothing.
set -euo pipefail

use_branch() {
    local package="$1" version="$2" repository="$3"
    if composer show --all --no-interaction "$package" "$version" > /dev/null 2>&1; then
        return
    fi
    echo "$package $version is not on Packagist yet: using release/$version"
    composer config "repositories.${package//\//-}" vcs "$repository"
    composer require --no-update --no-interaction "$package:dev-release/$version as $version"
}

use_branch hostkurd/flocms-cli 2.0.0 https://github.com/hostkurd/flocms-cli
use_branch hostkurd/flocms-api 1.2.0 https://github.com/hostkurd/flocms-api
