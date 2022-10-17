<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 16.03.18
 * Time: 12:04
 */

namespace Native\ApiClient\Auth;


use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Exception\WebDriverException;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Native\ApiClient\Exceptions\AuthException;

/**
 * Requires Selenium webdriver server
 *
 * @package Native\ApiClient\Auth
 */
class SourceLogin
{
    
    const DRIVER_CONNECTION_TIMEOUT = 3000; // in ms
    
    const PAGE_LOAD_TIMEOUT = 10; // is seconds
    
    
    protected $driver;
    
    protected $pageLoadTimeout = self::PAGE_LOAD_TIMEOUT;
    
    protected $isScreenshotsAllowed = false;
    
    protected $screenshotsSavePath = '';
    
    /**
     * @param       $driverHost
     * @param array $options
     *
     * possible options:
     *  - driverTimeout
     *  - pageLoadTimeout
     *  - isScreenshotsAllowed
     *  - screenshotsSavePath
     *
     * @throws \Native\ApiClient\Exceptions\AuthException
     */
    protected function __construct($driverHost, $options = [])
    {
        if (empty($driverHost)) {
            throw new AuthException("driver host is not provided");
        }
        
        $driverConnectionTimeout = self::DRIVER_CONNECTION_TIMEOUT;
        
        if (isset($options['driverTimeout'])) {
            $driverConnectionTimeout = $options['driverTimeout'];
        }
        
        if (isset($options['pageLoadTimeout'])) {
            $this->pageLoadTimeout = $options['pageLoadTimeout'];
        }
        
        if (isset($options['isScreenshotsAllowed'])) {
            $this->isScreenshotsAllowed = (bool)$options['isScreenshotsAllowed'];
        }
        
        
        if (isset($options['screenshotsSavePath']) && file_exists($options['screenshotsSavePath'])) {
            $this->screenshotsSavePath = $options['screenshotsSavePath'];
        }
        
        
        $chromeOptions = new ChromeOptions();
        if (!isset($options['noHeadless']) || !$options['noHeadless']) {
            $chromeOptions->addArguments(['--headless']);
        }
        $caps = DesiredCapabilities::chrome();
        $caps->setCapability(ChromeOptions::CAPABILITY, $chromeOptions);
        
        $this->driver = RemoteWebDriver::create($driverHost, $caps, $driverConnectionTimeout);
    }
    
    /**
     * @param string $driverHost
     * @param array  $options
     *
     * @return \Native\ApiClient\Auth\SourceLogin
     * @throws \Native\ApiClient\Exceptions\AuthException
     */
    public static function create($driverHost, $options = [])
    {
        try {
            return new self($driverHost, $options);
        } catch (\Exception $e) {
            throw new AuthException("webdriver failed: {$e->getMessage()} ({$e->getCode()})");
        }
    }
    
    /**
     * @param $loginUrl
     * @param $sourceLogin
     * @param $sourcePassword
     *
     * @return string
     * @throws \Native\ApiClient\Exceptions\AuthException
     */
    public function login($loginUrl, $sourceLogin, $sourcePassword)
    {
        try {
            $this->driver->get($loginUrl);
            $this->takeScreenshot('start');
            
            // здесь сценарий для Selenium
            
            $finalUrl = $this->driver->getCurrentURL();
            
            $this->driver->quit();
            
            return $finalUrl;
        } catch (WebDriverException $e) {
            throw new AuthException("webdriver error: {$e->getMessage()} ({$e->getCode()})");
        } catch (\Exception $e) {
            throw new AuthException("common error while login procedure: {$e->getMessage()} ({$e->getCode()})");
        }
    }
    
    /**
     * @param $name
     */
    protected function takeScreenshot($name)
    {
        if ($this->isScreenshotsAllowed) {
            $this->driver->takeScreenshot($this->screenshotsSavePath . $name);
        }
    }
}