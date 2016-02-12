<?php

class CDNRewriteRequestFilter implements RequestFilter
{

    /**
     * Enable rewriting of asset urls
     * @var bool
     */
    private static $cdn_rewrite = false;

    /**
     * Enable rewrite in admin area
     * @var bool
     */
    private static $enable_in_backend = false;

    /**
     * Enable rewrite in dev mode
     * @var bool
     */
    private static $enable_in_dev = false;

    /**
     * Rewrite all of these folders
     * @var array
     */
    private static $rewrites = array();

    /**
     * Filter executed before a request processes
     *
     * @param SS_HTTPRequest $request Request container object
     * @param Session $session Request session
     * @param DataModel $model Current DataModel
     * @return boolean Whether to continue processing other filters. Null or true will continue processing (optional)
     */
    public function preRequest(SS_HTTPRequest $request, Session $session, DataModel $model)
    {
        return true;
    }

    /**
     * Filter executed AFTER a request
     *
     * @param SS_HTTPRequest $request Request container object
     * @param SS_HTTPResponse $response Response output object
     * @param DataModel $model Current DataModel
     * @return boolean Whether to continue processing other filters. Null or true will continue processing (optional)
     */
    public function postRequest(SS_HTTPRequest $request, SS_HTTPResponse $response, DataModel $model)
    {
        if (!self::isEnabled()) {
            return true;
        }

        $body = $response->getBody();
        $response->setBody(self::replaceCDN($body));

        return true;
    }

    /**
     * Checks if cdn rewrite is enabled
     * @return bool
     */
    public static function isEnabled()
    {
        $general = Config::inst()->get('CDNRewriteRequestFilter', 'cdn_rewrite');
        $notDev = !Director::isDev() || Config::inst()->get('CDNRewriteRequestFilter', 'enable_in_dev');
        $notBackend = !self::isBackend() ||  Config::inst()->get('CDNRewriteRequestFilter', 'enable_in_backend');

        return $general && $notDev && $notBackend;
    }

    /**
     * Helper method to check if we're in backend (LeftAndMain) or frontend
     * Controller::curr() doesn't return anything, so i cannot check it...
     * @return bool
     */
    public static function isBackend()
    {
        $url = array_key_exists('url', $_GET) ? $_GET['url'] : '';
        return !Config::inst()->get('SSViewer', 'theme_enabled') || strpos($url, 'admin') === 1;
    }

    /**
     * replaces links to assets in src and href attributes to point to a given cdn domain
     *
     * @param $body
     * @return mixed|void
     */
    public static function replaceCDN($body)
    {
        $replace_rules = self::getReplaceRules();

        // Run the actual string replace using arrays to improve the process wherever possible
        foreach ($replace_rules as $replace => $searches) {
            $body = str_replace($searches, $replace, $body);
        }

        return $body;
    }

    /**
     * Create an ordered array of all the rules for string replace
     *
     * @return array
     */
    public static function getReplaceRules()
    {
        $isHTTPS = Director::is_https();
        $replace_rules = array();
        $search_keys = Config::inst()->get('CDNRewriteRequestFilter', 'rewrites');

        // legacy support
        $default_cdn = Config::inst()->get('CDNRewriteRequestFilter', 'cdn_domain');
        if (Config::inst()->get('CDNRewriteRequestFilter', 'rewrite_assets')) {
            $search_keys["assets"] = $default_cdn;
        }

        if (Config::inst()->get('CDNRewriteRequestFilter', 'rewrite_themes')) {
            $search_keys["themes"] = $default_cdn;
        }

        // Create an array of replace => [search] pairs
        foreach ($search_keys as $search_key => $cdn) {

            // if http / https have been provided, pick the appropriate one for this rewrite
            if (is_array($cdn)) {
                if ($isHTTPS && isset($cdn["https"])) {
                    $cdn = $cdn["https"];
                } elseif (!$isHTTPS && isset($cdn["http"])) {
                    $cdn = $cdn["http"];
                } else {
                    // if the user doesn't want a rewrite for a specific protocol, skip this rewrite
                    continue;
                }
            }

            $replace_rules['src="' . $cdn . '/' . $search_key . '/'] = array(
                'src="' . $search_key . '/',
                'src="/' . $search_key . '/'
            );

            $replace_rules['src=\"' . $cdn . '/' . $search_key . '/'] = array(
                '\'src=\"/' . $search_key . '/\''
            );

            $replace_rules['href="' . $cdn . '/' . $search_key . '/'] = array(
                'href="/' . $search_key . '/'
            );

            $replace_rules[$cdn . '/' . $search_key . '/'] = array(
                Director::absoluteBaseURL() . $search_key . '/'
            );

            if (Config::inst()->get('CDNRewriteRequestFilter', 'search_inline')) {
                $replace_rules['url(\'' . $cdn . '/' . $search_key . '/'] = array(
                    'url(\'/' . $search_key . '/',
                    'url(\'' . Director::absoluteBaseURL() . $search_key . '/'
                );

                $replace_rules['url(' . $cdn . '/' . $search_key . '/'] = array(
                    'url(/' . $search_key . '/',
                    'url(' . Director::absoluteBaseURL() . $search_key . '/'
                );
            }
        }

        return $replace_rules;
    }
}
