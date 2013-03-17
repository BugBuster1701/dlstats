<?php if (! defined('TL_ROOT')) die('You can not access this file directly!');

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2012 Leo Feyer
 * 
 * Modul Download Statistics, Helperclass
 *
 * 
 * PHP version 5
 * @copyright  Glen Langer (BugBuster) 2012
 * @author     BugBuster
 * @package    GLDLStats
 * @license    LGPL
 * @filesource
 */

/**
 * Class DlstatsHelper
 * 
 * @copyright  Glen Langer 2012
 * @author     Glen Langer 
 * @package    GLDLStats
 * @license    LGPL
 */
class DlstatsHelper extends Controller
{

	/**
	 * The IP address
	 * @var string
	 */
	protected $IP = false;

	/**
	 * The IP version
	 * @var string
	 */
	protected $IP_Version = '';

	/**
	 * The IP filter status
	 * @var boolean
	 */
	protected $IP_Filter = false;

	/**
	 * The BE filter status
	 * @var boolean
	 */
	protected $BE_Filter = false;

	/**
	 * The BOT filter status
	 * @var boolean
	 */
	protected $BOT_Filter = false;

	/**
	 * Status, download logging yes or no
	 * @var boolen
	 */
	protected $DL_LOG = true;

	/**
	 * Initialize the object
	 */
	protected function __construct()
	{
		parent::__construct();
		$this->CheckIP();
		$this->CheckBE();
		$this->CheckBot();
		$this->setDL_LOG();
	
		//$this->log("DLSTAT_DEBUG: IPV:".$this->IP_Version." IPF:".(0+$this->IP_Filter)." BEF:".(0 + $this->BE_Filter) ." Bot:".(0+$this->BOT_Filter)."" , "DlstatsHelper", TL_CONFIGURATION );
	}

	/**
	 * Set DL_LOG, true: logging OK (default), false not OK
	 * 
	 * @return void
	 * @access protected
	 */
	protected function setDL_LOG()
	{
		if ($this->IP_Filter === true || $this->BE_Filter === true || $this->BOT_Filter === true)
		{
			$this->DL_LOG = false;
		}
	
	}

	/**
	 * IP =  IPv4 or IPv6 ?
	 *
	 * @param string $ip	IP Address (IPv4 or IPv6)
	 * @return mixed		false: no valid IPv4 and no valid IPv6
	 * 						"IPv4" : IPv4 Address
	 * 						"IPv6" : IPv6 Address
	 * @access protected
	 */
	protected function CheckIPVersion($UserIP = false)
	{
		// Test for IPv4
		if (ip2long($UserIP) !== false)
		{
			$this->IP_Version = "IPv4";
			return $this->IP_Version;
		}
		
		// Test for IPv6
		if (substr_count($UserIP, ":") < 2)
			$this->IP_Version = false;
			return false; // ::1 or 2001::0db8
		if (substr_count($UserIP, "::") > 1)
			$this->IP_Version = false;
			return false; // one allowed
		$groups = explode(':', $UserIP);
		$num_groups = count($groups);
		if (($num_groups > 8) || ($num_groups < 3))
			$this->IP_Version = false;
			return false;
		$empty_groups = 0;
		foreach ($groups as $group)
		{
			$group = trim($group);
			if (! empty($group) && ! (is_numeric($group) && ($group == 0)))
			{
				if (! preg_match('#([a-fA-F0-9]{0,4})#', $group))
					$this->IP_Version = false;
					return false;
			}
			else
				++ $empty_groups;
		}
		if ($empty_groups < $num_groups)
		{
			$this->IP_Version = "IPv6";
			return $this->IP_Version;
		}
		$this->IP_Version = false;
		return false; // no (valid) IP Address
	}

	/**
	 * IP Check
	 * Set IP, detect the IP version and calls the method CheckIPv4 respectively CheckIPv6.
	 * 
	 * @param string   User IP, optional for tests
	 * @return boolean true when bot found over IP
	 * @access protected
	 */
	protected function CheckIP($UserIP = false)
	{
		// Check if IP present
		if ($UserIP === false)
		{
			if ($this->Environment->remoteAddr)
			{
				if (strpos($this->Environment->remoteAddr, ',') !== false) //first IP 
				{
					$this->IP = trim(substr($this->Environment->remoteAddr, 0, strpos($this->Environment->remoteAddr, ',')));
				}
				else
				{
					$this->IP = trim($this->Environment->remoteAddr);
				}
			}
			else
			{
				return false; // No IP, no search.
			}
		}
		else
		{
			$this->IP = $UserIP;
		}
		// IPv4 or IPv6 ?
		switch ($this->CheckIPVersion($this->IP))
		{
			case "IPv4":
				if ($this->CheckIPv4($this->IP) === true)
				{
					$this->IP_Filter = true;
					return $this->IP_Filter;
				}
				break;
			case "IPv6":
				if ($this->CheckIPv6($this->IP) === true)
				{
					$this->IP_Filter = true;
					return $this->IP_Filter;
				}
				break;
			default:
				$this->IP_Filter = false;
				return $this->IP_Filter;
				break;
		}
		$this->IP_Filter = false;
		return $this->IP_Filter;
	}

	/**
	 * IP Check for IPv6
	 * 
	 * @param string   User IP
	 * @return boolean true when own IP found in localconfig definitions
	 * @access protected
	 */
	protected function CheckIPv6($UserIP = false)
	{
		// Check if IP present
		if ($UserIP === false)
		{
			return false; // No IP, no search.
		}
		// search for user bot IP-filter definitions in localconfig.php
		if (isset($GLOBALS['DLSTATS']['BOT_IPV6']))
		{
			foreach ($GLOBALS['DLSTATS']['BOT_IPV6'] as $lineleft)
			{
				$network = explode("/", trim($lineleft));
				if (! isset($network[1]))
				{
					$network[1] = 128;
				}
				if ($this->dlstatsIPv6InNetwork($UserIP, $network[0], $network[1]))
				{
					return true; // IP found
				}
			}
		}
		return false;
	}

	/**
	 * IP Check for IPv4
	 * 
	 * @param string   User IP
	 * @return boolean true when own IP found in localconfig definitions
	 * @access protected
	 */
	protected function CheckIPv4($UserIP = false)
	{
		// Check if IP present
		if ($UserIP === false)
		{
			return false; // No IP, no search.
		}
		// search for user bot IP-filter definitions in localconfig.php
		if (isset($GLOBALS['DLSTATS']['BOT_IPV4']))
		{
			foreach ($GLOBALS['DLSTATS']['BOT_IPV4'] as $lineleft)
			{
				$network = explode("/", trim($lineleft));
				if (! isset($network[1]))
				{
					$network[1] = 32;
				}
				if ($this->dlstatsIPv4InNetwork($UserIP, $network[0], $network[1]))
				{
					return true; // IP found
				}
			}
		}
		return false;
	}

	/**
	 * Helperfunction, if IPv4 in NET_ADDR/NET_MASK
	 *
	 * @param string $ip		IPv4 Address
	 * @param string $net_addr	Network, optional
	 * @param int    $net_mask	Mask, optional
	 * @return boolean
	 * @access protected
	 */
	protected function dlstatsIPv4InNetwork($ip, $net_addr = 0, $net_mask = 0)
	{
		if ($net_mask <= 0)
		{
			return false;
		}
		if (ip2long($net_addr) === false)
		{
			return false; //no IP
		}
		//php.net/ip2long : jwadhams1 at yahoo dot com
		$ip_binary_string  = sprintf("%032b", ip2long($ip));
		$net_binary_string = sprintf("%032b", ip2long($net_addr));
		return (substr_compare($ip_binary_string, $net_binary_string, 0, $net_mask) === 0);
	}

	/**
	 * Helperfunction, Replace '::' with appropriate number of ':0'
	 *
	 * @param string $Ip	IP Address
	 * @return string		IP Address expanded
	 * @access protected
	 */
	protected function dlstatsIPv6ExpandNotation($Ip)
	{
		if (strpos($Ip, '::') !== false)
			$Ip = str_replace('::', str_repeat(':0', 8 - substr_count($Ip, ':')) . ':', $Ip);
		if (strpos($Ip, ':') === 0)
			$Ip = '0' . $Ip;
		return $Ip;
	}

	/**
	 * Helperfunction, Convert IPv6 address to an integer
	 *
	 * Optionally split in to two parts.
	 *
	 * @see http://stackoverflow.com/questions/420680/
	 * @param string $Ip			IP Address
	 * @param int $DatabaseParts	1 = one part, 2 = two parts (array)
	 * @return mixed				string      / array
	 * @access protected
	 */
	protected function dlstatsIPv6ToLong($Ip, $DatabaseParts = 1)
	{
		$Ip = $this->dlstatsIPv6ExpandNotation($Ip);
		$Parts = explode(':', $Ip);
		$Ip = array('', '');
		for ($i = 0; $i < 4; $i++)
			$Ip[0] .= str_pad(base_convert($Parts[$i], 16, 2), 16, 0, STR_PAD_LEFT);
		for ($i = 4; $i < 8; $i++)
			$Ip[1] .= str_pad(base_convert($Parts[$i], 16, 2), 16, 0, STR_PAD_LEFT);
		
		if ($DatabaseParts == 2)
			return array(base_convert($Ip[0], 2, 10), base_convert($Ip[1], 2, 10));
		else
			return base_convert($Ip[0], 2, 10) + base_convert($Ip[1], 2, 10);
	}

	/**
	 * Helperfunction, if IPv6 in NET_ADDR/PREFIX
	 *
	 * @param string $UserIP        IP Address
	 * @param string $net_addr      NET_ADDR
	 * @param integer $net_mask     PREFIX
	 * @return boolean
	 * @access protected
	 */
	protected function dlstatsIPv6InNetwork($UserIP, $net_addr = 0, $net_mask = 0)
	{
		if ($net_mask <= 0)
		{
			return false;
		}
		// UserIP to bin
		$UserIP = $this->dlstatsIPv6ExpandNotation($UserIP);
		$Parts = explode(':', $UserIP);
		$Ip = array('', '');
		for ($i = 0; $i < 8; $i++)
			$Ip[0] .= str_pad(base_convert($Parts[$i], 16, 2), 16, 0, STR_PAD_LEFT);
		
		// NetAddr to bin
		$net_addr = $this->dlstatsIPv6ExpandNotation($net_addr);
		$Parts = explode(':', $net_addr);
		for ($i = 0; $i < 8; $i++)
			$Ip[1] .= str_pad(base_convert($Parts[$i], 16, 2), 16, 0, STR_PAD_LEFT);
		
		// compare the IPs
		return (substr_compare($Ip[0], $Ip[1], 0, $net_mask) === 0);
	}

	/**
	 * BE Login Check
	 * basiert auf Frontend.getLoginStatus
	 * 
	 * @return boolean
	 * @access protected
	 */
	protected function CheckBE()
	{
		$strCookie = 'BE_USER_AUTH';
		//$hash = sha1(session_id() . $this->Environment->ip . $strCookie);
		$hash = sha1(session_id() . (!$GLOBALS['TL_CONFIG']['disableIpCheck'] ? $this->Environment->ip : '') . $strCookie);
		if ($this->Input->cookie($strCookie) == $hash)
		{
			$objSession = $this->Database->prepare("SELECT * FROM tl_session WHERE hash=? AND name=?")
											->limit(1)
											->execute($hash, $strCookie);
			if ($objSession->numRows && 
				$objSession->sessionID == session_id() && 
				//$objSession->ip == $this->Environment->ip &&
				($GLOBALS['TL_CONFIG']['disableIpCheck'] || $objSession->ip == $this->Environment->ip) && 
			 	($objSession->tstamp + $GLOBALS['TL_CONFIG']['sessionTimeout']) > time())
			{
				$this->BE_Filter = true;
				return $this->BE_Filter;
			}
		}
		$this->BE_Filter = false;
		return $this->BE_Filter;
	}

	/**
	 * dlstatsAnonymizeIP - Anonymize the last byte(s) of visitors IP addresses, if enabled
	 * 
	 * @return mixed     string = IP Address anonymized, false for "no IP"
	 * @access protected
	 */
	protected function dlstatsAnonymizeIP()
	{
		if ($this->IP_Version === false)
		{
			return '0.0.0.0';
		}
		if (isset($GLOBALS['TL_CONFIG']['privacyAnonymizeIp']) && 
				  $GLOBALS['TL_CONFIG']['privacyAnonymizeIp'] == false)
		{
			// Anonymize is disabled
			return ($this->IP === false) ? '0.0.0.0' : $this->IP;
		}
		switch ($this->IP_Version)
		{
			case "IPv4":
				$arrIP = explode('.', $this->IP);
				$arrIP[3] = 0;
				return implode('.', $arrIP);
				break;
			case "IPv6":
				$arrIP = explode(':', $this->dlstatsIPv6ExpandNotation($this->IP));
				$arrIP[7] = 0;
				$arrIP[8] = 0;
				return implode(':', $arrIP);
				break;
			default:
				return '0.0.0.0';
		}
	}

	/**
	 * dlstatsAnonymizeDomain - Anonymize the Domain of visitors, if enabled
	 *
	 * @return string     Domain anonymized, if DNS entry exists
	 * @access protected
	 */
	protected function dlstatsAnonymizeDomain()
	{
		if ($this->IP_Version === false || $this->IP === '0.0.0.0')
		{
			return '';
		}
		if (isset($GLOBALS['TL_CONFIG']['privacyAnonymizeIp']) && 
				  $GLOBALS['TL_CONFIG']['privacyAnonymizeIp'] == false)
		{
			// Anonymize is disabled
			$domain = gethostbyaddr($this->IP);
			return ($domain == $this->IP) ? '' : $domain;
		}
		// Anonymize is enabled
		$arrURL = explode('.', gethostbyaddr($this->IP));
		$tld  = array_pop($arrURL);
		$host = array_pop($arrURL);
		if (is_numeric($tld) === false)
		{
			return (strlen($host)) ? $host . '.' . $tld : $tld;
		}
		else
		{
			return '';
		}
	}

	/**
	 * Bot Check
	 * 
	 * @return mixed    true or string if Bot found, false if not
	 * @access protected
	 */
	protected function CheckBot()
	{
		// Import Helperclass ModuleBotDetection
		$this->import('ModuleBotDetection');
		//Call BD_CheckBotAgent
		$test01 = $this->ModuleBotDetection->BD_CheckBotAgent();
		if ($test01 === true)
		{
			$this->BOT_Filter = true;
			return $this->BOT_Filter;
		}
		//Call BD_CheckBotIP
		$test02 = $this->ModuleBotDetection->BD_CheckBotIP();
		if ($test02 === true)
		{
			$this->BOT_Filter = true;
			return $this->BOT_Filter;
		}
		//Call BD_CheckBotAgentAdvanced
		$test03 = $this->ModuleBotDetection->BD_CheckBotAgentAdvanced();
		if ($test03 !== false)
		{
			$this->BOT_Filter = true;
			return $test03; // Bot Name
		}
		// No Bots found
		return false;
	}
}

?>