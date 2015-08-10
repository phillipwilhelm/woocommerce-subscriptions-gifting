#!/usr/bin/env bash
# usage: travis.sh before|after

if [ $1 == 'before' ]; then

	# place a copy of woocommerce where the unit tests etc. expect it to be
	mkdir -p "../woocommerce"
	curl -L https://api.github.com/repos/woothemes/woocommerce/tarball/$WC_VERSION?access_token=$GITHUB_TOKEN | tar --strip-components=1 -zx -C "../woocommerce"

	# place a copy of woocommerce subscriptions where the unit tests etc. expect it to be
	mkdir -p "../woocommerce-subscriptions"
	curl -L https://api.github.com/repos/prospress/woocommerce-subscriptions/tarball/$WCS_VERSION?access_token=$GITHUB_TOKEN | tar --strip-components=1 -zx -C "../woocommerce-subscriptions"

fi