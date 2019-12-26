<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * This class takes the ip as a parameter and find the 
 * approximated location of the user.
 *
 * PHP version 5
 *
 * LICENSE: This source file is subject to version 3.01 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_01.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category   	Generic system methods
 * @package    	geo location
 * @author     	Frederik Yssing <fryss@pinchlabs.dk>
 * @copyright  	2012-2013 pinch
 * @license    	http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    	SVN: 1.0.0
 * @link       	          http://www.pinchlabs.dk
 * @since      	File available since Release 1.0.0
 */

class netGeo{
	/**
     * The ip adress to look up, if supplied.
	 * freegeoip will find the best ip if no Ip is given.
	 *
     * @var string Ip
     * @access public
	 * @static
     */	
	public static $Ip = '0';
	/**
     * Country code in letters eg. DK for Denmark.
	 *
     * @var string CountryCode
     * @access public
	 * @static
     */		
	public static $CountryCode = '';
	/**
     * @var string CountryName
     * @access public
	 * @static
     */		
	public static $CountryName = '';
	/**
     * @var string RegionCode
     * @access public
	 * @static
     */		
	public static $RegionCode = '';
	/**
     * @var string RegionName
     * @access public
	 * @static
     */		
	public static $RegionName = '';
	/**
     * @var string City
     * @access public
	 * @static
     */		
	public static $City = '';
	/**
     * @var string ZipCode
     * @access public
	 * @static
     */		
	public static $ZipCode = '';
	/**
     * @var string Latitude
     * @access public
	 * @static
     */		
	public static $Latitude = '';
	/**
     * @var string Longitude
     * @access public
	 * @static
     */		
	public static $Longitude = '';
	/**
     * @var string MetroCode
     * @access public
	 * @static
     */		
	public static $MetroCode = '';
	/**
     * @var string AreaCode
     * @access public
	 * @static
     */		
	public static $AreaCode = '';
	
	/**
	 * This method is used to ask freegeoip for geo information based on
	 * either the supplied IP or the IP of the asker.
	 * The answer from freegeoip is an XML file.
	 *
	 * @param string $ip The Ip address to ask on.   
	 *
	 * @return bool Returns TRUE on success or FALSE on failure.
	 *
	 * @access public
	 * @static
	 * @since Method available since Release 1.0.0
	 */		
	public static function getNetGeo($ip = 0){
		if($ip){
			$url = 'http://freegeoip.net/xml/'.$ip;
		} else {
			$url = 'http://freegeoip.net/xml/';
		}

		try{
			$result = file_get_contents($url, false);
			if(!$result){
				throw new Exception('Could not get a response from freegeoip.net');
				return false;
			} else {
				self::pickDataFromXML($result);
			}
		}
		catch (Exception $e){
			self::DBug('Caught exception: '. $e->getMessage(). ' in method: '.__METHOD__.' in file: '.__FILE__);
			return false;
		}	
		return true;
	}	
	
	/**
	 * This method finds the relevant data from the returned xml file.
	 * It then assigns those data to the variables.
	 *
	 * @param string $file The xml file with the data found..   
	 *
	 * @return bool Returns TRUE on success or FALSE on failure.
	 *
	 * @access public
	 * @static
	 * @since Method available since Release 1.0.0
	 * @todo use a pointer to the xml file.
	 */			
	public static function pickDataFromXML($file){
		$XMLFile = '';
		try{
			$XMLFile = simplexml_load_string($file);
			if(!$XMLFile){
				throw new Exception('Could not load XML file!');
			} else {
				self::$Ip = $XMLFile->Ip;
				self::$CountryCode = $XMLFile->CountryCode;
				self::$CountryName = $XMLFile->CountryName;
				self::$RegionCode = $XMLFile->RegionCode;
				self::$RegionName = $XMLFile->RegionName;
				self::$City = $XMLFile->City;
				self::$ZipCode = $XMLFile->ZipCode;
				self::$Latitude = $XMLFile->Latitude;
				self::$Longitude = $XMLFile->Longitude;
				self::$MetroCode = $XMLFile->MetroCode;
				self::$AreaCode = $XMLFile->AreaCode;
				//unlink($XMLFile);				
			}
		}
		catch (Exception $e){
			self::DBug('Caught exception: '. $e->getMessage(). ' in method: '.__METHOD__.' in file: '.__FILE__);
			return false;
		}
		return true;
	}	

	/*
	 * Local variables:
	 * tab-width: 4
	 * c-basic-offset: 4
	 * c-hanging-comment-ender-p: nil
	 * End:
	 */		
}
?>