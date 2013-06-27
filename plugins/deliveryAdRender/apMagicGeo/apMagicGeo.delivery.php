<?php

/**
 * apMagicGeo for the OpenX ad server
 *
 * @author Matteo Beccati
 * @copyright 2010-2011 AdserverPlugins.com - All rights reserved
 *
 * $Id: apMagicGeo.delivery.php 551 2011-02-28 10:17:39Z matteo.beccati $
 */

function Plugin_deliveryAdRender_apMagicGeo_apMagicGeo_Delivery_postAdRender(&$code, $aBanner)
{
    $oMagic = new AP_MagicGeo();
    $code = $oMagic->convert($code);
}

class AP_MagicGeo
{
    protected $code;
    protected $aData;

    private $aVariables = array(
        'continent_code' => 'getContinentCode',
        'continent'      => 'getContinent',
        'country_code'   => false,
        'country'        => 'getCountry',
        'region_code'    => 'getRegionCode',
        'region'         => 'getRegion',
        'city'           => false,
        'postal_code'    => false,
        'latitude'       => false,
        'longitude'      => false,
        'dma'            => 'getDMA',
        'dma_code'       => false,
        'us_metro'       => 'getDMA',
        'us_metro_code'  => 'getDMACode',
        'area_code'      => false,
        'organisation'   => false,
        'isp'            => false,
    );

    public function __construct($aData = null)
    {
        if (isset($aData)) {
            $this->aData = $aData;
        } elseif (isset($GLOBALS['_MAX']['CLIENT_GEO'])) {
            $this->aData = $GLOBALS['_MAX']['CLIENT_GEO'];
        }
    }

    public function convert($code)
    {
        if (!empty($code) && preg_match_all('#\{geo\.([a-z_]+?)(\.enc)?\}#', $code, $aMatches)) {
            $aReplace = array();
            foreach ($aMatches[1] as $k => $v) {
                if (isset($this->aVariables[$v])) {
                    $enc = empty($aMatches[2][$k]) ? 0 : 1;
                    $aReplace[$v][$enc] = $aMatches[0][$k];
                }
            }
            foreach ($aReplace as $name => $aVariants) {
                if ($this->aVariables[$name]) {
                    $text = $this->{$this->aVariables[$name]}();
                } else {
                    $text = empty($this->aData[$name]) ? '' : $this->aData[$name];
                }
                for ($enc = 0; $enc <= 1; $enc++) {
                    if (isset($aVariants[$enc])) {
                        $text = $enc ? urlencode($text) : $text;
                        $code = str_replace($aVariants[$enc], $text, $code);
                    }
                }
            }
        }

        return $code;
    }

    private function getResPath($fName)
    {
        return MAX_PATH.$GLOBALS['_MAX']['CONF']['pluginPaths']['plugins'].'/deliveryLimitations/Geo/data/'.$fName;
    }

    protected function getContinentCode()
    {
        include $this->getResPath('res-continent.inc.php');
        if (!empty($this->aData['country_code']) && isset($OA_Geo_continent[$this->aData['country_code']])) {
            return $OA_Geo_continent[$this->aData['country_code']];
        }
        return '';
    }

    protected function getContinent()
    {
        include $this->getResPath('res-continent.inc.php');
        if (!empty($this->aData['country_code']) && isset($OA_Geo_continent[$this->aData['country_code']])) {
            return $OA_Geo_cont_name[$OA_Geo_continent[$this->aData['country_code']]];
        }
        return '';
    }

    protected function getCountry()
    {
        include $this->getResPath('res-iso3166.inc.php');
        if (!empty($this->aData['country_code']) && isset($OA_Geo_ISO3166[$this->aData['country_code']])) {
            return $OA_Geo_ISO3166[$this->aData['country_code']];
        }
        return '';
    }

    protected function getRegion()
    {
        if (!empty($this->aData['country_code'])) {
            if ($this->aData['country_code'] == 'US' || $this->aData['country_code'] == 'CA') {
                return $this->getRegionUS();
            } else {
                return $this->getRegionFIPS();
            }
        }
        return '';
    }

    protected function getRegionUS()
    {
        include $this->getResPath('res-iso3166-2.inc.php');
        if (!empty($this->aData['region']) && isset($OA_Geo_ISO3166_2[$this->aData['country_code']][$this->aData['region']])) {
            return $OA_Geo_ISO3166_2[$this->aData['country_code']][$this->aData['region']];
        }
        return '';
    }

    protected function getRegionFIPS()
    {
        include $this->getResPath('res-fips.inc.php');
        if (!empty($this->aData['region']) && isset($OA_Geo_FIPS[$this->aData['country_code']][$this->aData['region']])) {
            return $OA_Geo_FIPS[$this->aData['country_code']][$this->aData['region']];
        }
        return '';
    }

    protected function getRegionCode()
    {
        return empty($this->aData['region']) ? '' : $this->aData['region'];
    }

    protected function getDMA()
    {
        include $this->getResPath('res-dmacodes.inc.php');
        if (!empty($this->aData['dma_code']) && isset($OA_Geo_DmaCodes[$this->aData['dma_code']])) {
            return $OA_Geo_DmaCodes[$this->aData['dma_code']];
        }
        return '';
    }

    protected function getDMACode()
    {
        if (!empty($this->aData['dma_code']) && isset($OA_Geo_DmaCodes[$this->aData['dma_code']])) {
            return $this->aData['dma_code'];
        }
        return '';
    }
}
