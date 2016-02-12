silverstripe-cdnrewrite
==========================

Rewrites all links for assets to use a cdn instead. It's not responsible to upload or sync the files anywhere. Some CDNs can do this for you.

## Requirements

* [`Silverstripe 3.1.* framework`](https://github.com/silverstripe/silverstripe-framework)
* [`Silverstripe 3.1.* CMS`](https://github.com/silverstripe/cms)

## Installation

Download and install manually or use composer.

## Configuration

You have to enable this filter manually using `CDNRewriteRequestFilter.cdn_rewrite` config var.
Also define `CDNRewriteRequestFilter.rewrites` with individual CDN protocols and hosts as below.

Your config.yml might look like:

```yml
CDNRewriteRequestFilter:
  cdn_rewrite: true #global switch
  enable_in_dev: false #do not enable this in dev
  enable_in_backend: false #do not enable this in CMS
  search_inline: false #do not search for inline references to folders
  rewrites:
    assets:
      http: 'http://unsecure.cdn.com'
      https: 'https://secure.cdn.com'
    themes: '//www.themescdn.com'
    framework/thirdparty: 'http://www.thirdpartycdn.com'
```

Note that you can supply a separate CDN for individual folders, and designate a specific CDN for a specific protocol or you can just designate a single CDN for both http and https as above.

## Legacy Notes
For previous installs where rewrites were using `cdn_domain` , `rewrite_themes` and `rewrite_assets`, they are still supported for now.